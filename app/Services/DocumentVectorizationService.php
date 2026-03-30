<?php

namespace App\Services;

use App\DataTransferObjects\DocumentEmbeddingData;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocumentVectorizationService
{
    public function __construct(
        private DocumentTextExtractorService $textExtractor,
        private ChatGPTServiceV2 $chatGPT,
        private QdrantService $qdrant,
        private CacheService $cacheService,
    ) {
    }

    public function vectorizeAndIndex(UploadedFile $file): int
    {
        Log::info('Starting document vectorization', ['filename' => $file->getClientOriginalName()]);

        $this->ensureCollectionReady();

        $text = $this->textExtractor->extract($file);
        Log::info('Text extracted', ['length' => strlen($text)]);

        $chunks = $this->chatGPT->createChunks($text);
        Log::info('Chunks created', ['count' => count($chunks)]);

        $chunkTexts = array_values($chunks);
        $embeddings = $this->chatGPT->embedTexts($chunkTexts);
        Log::info('Embeddings generated', ['count' => count($embeddings)]);

        $points = [];
        $uploadDate = now();
        $filename = $file->getClientOriginalName();

        foreach ($embeddings as $index => $embedding) {
            $embeddingData = new DocumentEmbeddingData(
                filename: $filename,
                chunkIndex: $index,
                chunkText: $chunkTexts[$index],
                vector: $embedding,
                uploadDate: $uploadDate,
            );

            $pointId = $this->generatePointId($filename, $index);
            $points[] = $embeddingData->toQdrantPoint($pointId);
        }

        $this->qdrant->upsertPoints($points);
        Log::info('Points upserted to Qdrant', ['count' => count($points)]);

        return count($points);
    }

    public function searchDocuments(string $query, int $topK = 10, float $minScore = 0.0): array
    {
        Log::info('Starting document search', ['query' => $query, 'top_k' => $topK]);

        $this->ensureCollectionReady();

        $embeddings = $this->chatGPT->embedTexts([$query]);
        $queryVector = $embeddings[0];
        $embeddingHash = hash('sha256', json_encode($queryVector));

        $cached = $this->cacheService->getSearchResults($embeddingHash, $topK, $minScore);
        if ($cached !== null) {
            Log::info('Search results from cache');
            return $cached;
        }

        $searchResults = $this->qdrant->searchByVector($queryVector, $topK);
        Log::info('Search results retrieved', ['count' => count($searchResults)]);

        $results = [];
        foreach ($searchResults as $result) {
            if ($result['score'] < $minScore) {
                continue;
            }

            $payload = $result['payload'] ?? [];

            $results[] = [
                'score' => $result['score'],
                'document_name' => $payload['filename'] ?? 'unknown',
                'chunk_index' => $payload['chunk_index'] ?? 0,
                'text' => $payload['chunk_text'] ?? '',
            ];
        }

        $this->cacheService->putSearchResults($embeddingHash, $topK, $minScore, $results);

        return $results;
    }

    private function ensureCollectionReady(): void
    {
        if ($this->qdrant->ensureCollectionExists()) {
            return;
        }

        Log::info('Qdrant collection not found, creating automatically', [
            'collection' => config('qdrant.collection_name'),
        ]);

        $this->qdrant->createCollection();
    }

    private function generatePointId(string $filename, int $chunkIndex): string
    {
        $hash = hash('sha1', strtolower(trim($filename)) . '|' . $chunkIndex);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12),
        );
    }
}
