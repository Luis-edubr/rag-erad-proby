<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\RagAnswerRequest;
use App\Http\Requests\RagSearchRequest;
use App\Services\ChatGPTServiceV2;
use App\Services\DocumentVectorizationService;
use Illuminate\Http\JsonResponse;

class RagController
{
    public function __construct(
        private DocumentVectorizationService $vectorizationService,
        private ChatGPTServiceV2 $chatGPT,
    ) {
    }

    public function search(RagSearchRequest $request): JsonResponse
    {
        $query = $request->validated('query');
        $topK = $request->validated('top_k') ?? 10;
        $minScore = $request->validated('min_score') ?? 0.0;

        $results = $this->vectorizationService->searchDocuments($query, $topK, $minScore);

        return response()->json([
            'query' => $query,
            'top_k' => $topK,
            'min_score' => $minScore,
            'results' => $results,
            'count' => count($results),
        ]);
    }

    public function answer(RagAnswerRequest $request): JsonResponse
    {
        $query = $request->validated('query');
        $topK = $request->validated('top_k') ?? 10;
        $minScore = $request->validated('min_score') ?? 0.75;

        $chunks = $this->vectorizationService->searchDocuments($query, $topK, $minScore);

        $context = collect($chunks)
            ->map(fn ($chunk) => $chunk['text'])
            ->implode("\n\n---\n\n");

        if (empty($context)) {
            return response()->json([
                'query' => $query,
                'chunks' => [],
                'answer' => 'No relevant documents found for the query.',
                'chunks_used' => 0,
            ]);
        }

        $answer = $this->chatGPT->generateAnswer($query, $context);

        return response()->json([
            'query' => $query,
            'chunks' => $chunks,
            'answer' => $answer,
            'chunks_used' => count($chunks),
        ]);
    }
}
