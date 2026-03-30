<?php

namespace App\Http\Controllers;

use App\Services\BaselineMetricsReader;
use App\Services\CacheService;
use App\Services\ChatGPTServiceV2;
use App\Services\DocumentVectorizationService;
use Illuminate\Http\Request;

class RagController extends Controller
{
    public function __construct(
        private DocumentVectorizationService $vectorizationService,
        private ChatGPTServiceV2 $chatGPTService,
        private CacheService $cacheService,
        private BaselineMetricsReader $baselineMetricsReader,
    ) {}

    /**
     * Display RAG interface
     */
    public function index()
    {
        return view('rag.index', [
            'baseline' => $this->baselineMetricsReader->getPayload(),
            'referenceBaseline' => config('rag.reference_baseline'),
            'pricingRef' => config('rag.pricing'),
        ]);
    }

    /**
     * Handle document upload and indexation
     */
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:txt,pdf|max:10240',
            ]);

            $file = $request->file('file');

            // Vectorize and index the document
            $pointsCount = $this->vectorizationService->vectorizeAndIndex($file);

            return response()->json([
                'success' => true,
                'filename' => $file->getClientOriginalName(),
                'points_count' => $pointsCount,
                'message' => "Documento indexado com sucesso ({$pointsCount} vetores)",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Handle RAG query and answer generation
     */
    public function ask(Request $request)
    {
        try {
            $validated = $request->validate([
                'query' => 'required|string|min:1|max:2000',
                'use_cache' => 'boolean',
                'top_k' => 'integer|min:1|max:100',
                'min_score' => 'numeric|min:0|max:1',
            ]);

            $query = $validated['query'];
            $useCache = $validated['use_cache'] ?? false;
            $topK = $validated['top_k'] ?? 5;
            $minScore = $validated['min_score'] ?? 0.0;

            $cacheHits = 0;
            $timings = [];

            // Track embedding time
            $embStart = microtime(true);
            $embedding = $this->chatGPTService->embedTexts([$query])[0];
            $timings['embedding'] = (microtime(true) - $embStart) * 1000;

            // Check if embedding was cached
            if ($this->cacheService->isEnabled()) {
                $cachedEmbedding = $this->cacheService->getEmbedding($query);
                if ($cachedEmbedding !== null) {
                    $cacheHits++;
                }
            }

            // Track vector search time
            $searchStart = microtime(true);
            $chunks = $this->vectorizationService->searchDocuments($query, $topK, $minScore);
            $timings['search'] = (microtime(true) - $searchStart) * 1000;

            // Check if search results were cached
            if ($this->cacheService->isEnabled() && ! empty($chunks)) {
                $cacheHits++;
            }

            // Track generation time
            $genStart = microtime(true);
            $context = implode("\n---\n", array_map(
                fn ($chunk) => "Document: {$chunk['document_name']}\nChunk {$chunk['chunk_index']}:\n{$chunk['text']}",
                $chunks
            ));

            $prompt = "Based on the following documents, answer the question: {$query}\n\nDocuments:\n{$context}";
            $answer = $this->chatGPTService->generateAnswer($query, $context);
            $timings['generation'] = (microtime(true) - $genStart) * 1000;

            // Check if answer was cached
            if ($this->cacheService->isEnabled()) {
                $cachedAnswer = $this->cacheService->getAnswer($prompt);
                if ($cachedAnswer !== null) {
                    $cacheHits++;
                }
            }

            // Calculate total time
            $timings['total'] = array_sum($timings);

            return response()->json([
                'success' => true,
                'query' => $query,
                'answer' => $answer,
                'chunks' => $chunks,
                'chunks_used' => count($chunks),
                'cache_hits' => $cacheHits,
                'cache_enabled' => $this->cacheService->isEnabled(),
                'timing' => $timings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
