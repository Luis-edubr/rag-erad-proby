<?php

namespace App\Services;

use App\Support\OpenAiCostEstimator;

class RagPipelineMetricsService
{
    public function __construct(
        private DocumentVectorizationService $vectorization,
        private ChatGPTServiceV2 $chatGPT,
    ) {}

    /**
     * Pipeline completo: embedding da query + busca Qdrant + geração (gpt-4o).
     *
     * @return array{
     *     answer: string,
     *     metrics: array{
     *         embedding_ms: float,
     *         vector_search_ms: float,
     *         llm_ms: float,
     *         total_ms: float,
     *         search_results_cache_hit: bool
     *     },
     *     cost: array{
     *         embedding_usd: float,
     *         vector_usd: float,
     *         llm_usd: float,
     *         total_usd: float
     *     }
     * }
     */
    public function answerWithStageMetrics(string $query, int $topK, float $minScore): array
    {
        $search = $this->vectorization->searchDocumentsWithMetrics($query, $topK, $minScore);
        $chunks = $search['results'];
        $m = $search['metrics'];

        $context = collect($chunks)
            ->map(fn ($chunk) => $chunk['text'])
            ->implode("\n\n---\n\n");

        $answer = '';
        $llmMs = 0.0;

        if ($context === '') {
            $answer = 'No relevant documents found for the query.';
        } else {
            $t0 = microtime(true);
            $answer = $this->chatGPT->generateAnswer($query, $context);
            $llmMs = (microtime(true) - $t0) * 1000;
        }

        $promptChars = strlen($query) + strlen($context) + 200;
        $llmBreakdown = OpenAiCostEstimator::estimateGpt4oUsdFromChars($promptChars, strlen($answer));

        $embeddingUsd = OpenAiCostEstimator::estimateEmbeddingUsdFromChars(strlen($query));
        $vectorUsd = 0.0;

        $cost = [
            'embedding_usd' => $embeddingUsd,
            'vector_usd' => $vectorUsd,
            'llm_usd' => $llmBreakdown['total_usd'],
            'total_usd' => $embeddingUsd + $vectorUsd + $llmBreakdown['total_usd'],
        ];

        $totalMs = $m['embedding_ms'] + $m['vector_search_ms'] + $llmMs;

        return [
            'answer' => $answer,
            'metrics' => [
                'embedding_ms' => $m['embedding_ms'],
                'vector_search_ms' => $m['vector_search_ms'],
                'llm_ms' => $llmMs,
                'total_ms' => $totalMs,
                'search_results_cache_hit' => $m['search_results_cache_hit'],
            ],
            'cost' => $cost,
        ];
    }
}
