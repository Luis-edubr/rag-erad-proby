<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ChatGPTServiceV2
{
    private const MODEL_GENERATION = 'gpt-4o';
    private const EMBEDDING_MODEL = 'text-embedding-ada-002';
    private const EMBEDDING_ENDPOINT = 'https://api.openai.com/v1/embeddings';
    private const GENERATION_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    private const OCR_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    private const MAX_TOKENS_PER_CHUNK = 2000;
    private const CHUNK_OVERLAP_TOKENS = 200;
    private const BATCH_SIZE = 100;
    private const BATCH_DELAY_MS = 100;
    private const RETRY_ATTEMPTS = 3;

    private Client $client;
    private string $apiKey;

    public function __construct(private CacheService $cacheService)
    {
        $this->apiKey = $this->resolveApiKey();

        $this->client = new Client([
            'timeout' => 60,
            'connect_timeout' => 10,
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    private function resolveApiKey(): string
    {
        $apiKey = config('services.openai.api_key')
            ?? config('services.openai.key')
            ?? env('OPENAI_API_KEY');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured. Set it in .env and run php artisan config:clear.');
        }

        return trim($apiKey);
    }

    public function createChunks(string $text): array
    {
        $estimatedTokens = ceil(strlen($text) / 4);

        if ($estimatedTokens <= self::MAX_TOKENS_PER_CHUNK) {
            return [$text];
        }

        return $this->splitByOverlap($text);
    }

    public function embedTexts(array $texts): array
    {
        $embeddings = [];
        $textsToEmbed = [];
        $embeddingMap = [];

        foreach ($texts as $index => $text) {
            $cached = $this->cacheService->getEmbedding($text);
            if ($cached !== null) {
                $embeddings[$index] = $cached;
            } else {
                $textsToEmbed[$index] = $text;
            }
        }

        if (empty($textsToEmbed)) {
            return $embeddings;
        }

        $batches = array_chunk($textsToEmbed, self::BATCH_SIZE, true);

        foreach ($batches as $batch) {
            $indices = array_keys($batch);
            $batchTexts = array_values($batch);
            $batchEmbeddings = $this->embedBatch($batchTexts);

            foreach ($indices as $originalIndex => $index) {
                $embeddings[$index] = $batchEmbeddings[$originalIndex] ?? [];
                $this->cacheService->putEmbedding($textsToEmbed[$index], $embeddings[$index]);
            }

            if (count($batches) > 1) {
                usleep(self::BATCH_DELAY_MS * 1000);
            }
        }

        ksort($embeddings);
        return array_values($embeddings);
    }

    public function generateAnswer(string $query, string $context): string
    {
        $prompt = "Use the following context to answer the question. If the context doesn't contain relevant information, say so.\n\nContext:\n{$context}\n\nQuestion:\n{$query}";

        $cached = $this->cacheService->getAnswer($prompt);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = $this->client->post(self::GENERATION_ENDPOINT, [
                'json' => [
                    'model' => self::MODEL_GENERATION,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.7,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $answer = $data['choices'][0]['message']['content'] ?? '';
            $this->cacheService->putAnswer($prompt, $answer);

            return $answer;
        } catch (GuzzleException $e) {
            Log::error('Generation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function callOcr(string $base64Content): string
    {
        try {
            $response = $this->client->post(self::OCR_ENDPOINT, [
                'json' => [
                    'model' => self::MODEL_GENERATION,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Extract all text from this PDF document. Return only the extracted text without any commentary.',
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => "data:application/pdf;base64,{$base64Content}",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $text = $data['choices'][0]['message']['content'] ?? '';

            Log::info('PDF OCR extraction completed');

            return $text;
        } catch (GuzzleException $e) {
            Log::error('OCR extraction failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function embedBatch(array $texts): array
    {
        for ($attempt = 1; $attempt <= self::RETRY_ATTEMPTS; $attempt++) {
            try {
                $response = $this->client->post(self::EMBEDDING_ENDPOINT, [
                    'json' => [
                        'model' => self::EMBEDDING_MODEL,
                        'input' => $texts,
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

                $embeddings = [];
                foreach ($data['data'] as $item) {
                    $embeddings[] = $item['embedding'];
                }

                return $embeddings;
            } catch (GuzzleException $e) {
                if ($e->getResponse() && $e->getResponse()->getStatusCode() === 429 && $attempt < self::RETRY_ATTEMPTS) {
                    $delay = min(1000 * (2 ** ($attempt - 1)), 5000);
                    Log::warning("Rate limited, retrying in {$delay}ms", ['attempt' => $attempt]);
                    usleep($delay * 1000);
                    continue;
                }

                Log::error('Embedding batch failed', ['error' => $e->getMessage()]);
                throw $e;
            }
        }

        return [];
    }

    private function splitByOverlap(string $text): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($sentences)) {
            return [$text];
        }

        $chunks = [];
        $currentChunk = '';
        $currentTokens = 0;

        foreach ($sentences as $sentence) {
            $sentenceTokens = ceil(strlen($sentence) / 4);

            if ($currentTokens + $sentenceTokens > self::MAX_TOKENS_PER_CHUNK && $currentChunk) {
                $chunks[] = trim($currentChunk);
                $overlapSize = ceil(self::MAX_TOKENS_PER_CHUNK * 0.1);
                $overlapTokens = 0;
                $overlapText = '';

                $reversedSentences = array_reverse(explode('. ', $currentChunk));
                foreach ($reversedSentences as $sent) {
                    if ($overlapTokens >= $overlapSize) {
                        break;
                    }
                    $overlapText = trim($sent) . '. ' . $overlapText;
                    $overlapTokens += ceil(strlen($sent) / 4);
                }

                $currentChunk = trim($overlapText);
                $currentTokens = $overlapTokens;
            }

            $currentChunk .= ' ' . $sentence;
            $currentTokens += $sentenceTokens;
        }

        if (trim($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return !empty($chunks) ? $chunks : [$text];
    }
}
