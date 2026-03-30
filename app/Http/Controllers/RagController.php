<?php

namespace App\Http\Controllers;

use App\Services\BaselineMetricsReader;
use App\Services\CacheService;
use App\Services\ChatGPTServiceV2;
use App\Services\DocumentVectorizationService;
use App\Services\QdrantService;
use App\Support\OpenAiCostEstimator;
use Illuminate\Http\Request;

class RagController extends Controller
{
    public function __construct(
        private DocumentVectorizationService $vectorizationService,
        private ChatGPTServiceV2 $chatGPTService,
        private CacheService $cacheService,
        private QdrantService $qdrantService,
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
            $topK = $validated['top_k'] ?? 5;
            $minScore = $validated['min_score'] ?? 0.0;

            $search = $this->vectorizationService->searchDocumentsWithMetrics($query, $topK, $minScore);
            $chunks = $search['results'];
            $m = $search['metrics'];

            $timings = [
                'embedding' => $m['embedding_ms'],
                'search' => $m['vector_search_ms'],
            ];

            $context = implode("\n---\n", array_map(
                fn ($chunk) => "Document: {$chunk['document_name']}\nChunk {$chunk['chunk_index']}:\n{$chunk['text']}",
                $chunks
            ));

            $genStart = microtime(true);
            $answer = $this->chatGPTService->generateAnswer($query, $context);
            $timings['generation'] = (microtime(true) - $genStart) * 1000;

            $costs = $this->calculateCosts($query, $context, $answer);

            $collectionPoints = $this->qdrantService->getCollectionPointsCount();
            $requestMetricsTable = $this->buildRequestMetricsTable(
                $timings,
                $query,
                $context,
                $topK,
                $minScore,
                $chunks,
                $answer,
                $collectionPoints,
                $costs
            );

            return response()->json([
                'success' => true,
                'query' => $query,
                'answer' => $answer,
                'chunks' => $chunks,
                'chunks_used' => count($chunks),
                'timing' => $timings,
                'cost' => $costs,
                'request_metrics_table' => $requestMetricsTable,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mesmo texto que {@see ChatGPTServiceV2::generateAnswer} envia ao modelo (para custo coerente com o artigo).
     */
    private function llmPromptForCost(string $query, string $context): string
    {
        return "Use the following context to answer the question. If the context doesn't contain relevant information, say so.\n\nContext:\n{$context}\n\nQuestion:\n{$query}";
    }

    /**
     * Volume = métricas reais desta requisição (tokens estimados chars/4; coleção Qdrant; resultados da busca).
     *
     * @param  array{embedding: float, search: float, generation: float}  $timings
     * @param  array<int, array<string, mixed>>  $chunks
     * @param  array{embedding: float, search: float, generation: float, total: float, llm_input?: float, llm_output?: float}  $costs
     * @return array{title: string, rows: list<array{component: string, volume: string, cost: string, time_ms: float}>}
     */
    private function buildRequestMetricsTable(
        array $timings,
        string $query,
        string $context,
        int $topK,
        float $minScore,
        array $chunks,
        string $answer,
        ?int $collectionPointsCount,
        array $costs,
    ): array {
        $c = config('rag.article_table');
        $pricing = config('rag.pricing');
        $queryTokens = max(1, (int) ceil(strlen($query) / 4));
        $fullPrompt = $this->llmPromptForCost($query, $context);
        $promptTokens = max(1, (int) ceil(strlen($fullPrompt) / 4));
        $answerTokens = max(1, (int) ceil(strlen($answer) / 4));
        $hits = count($chunks);

        $volEmbed = sprintf(
            "%s tokens estimados na query\n(≈ 1 token / 4 caracteres)",
            $this->formatIntPtBr($queryTokens)
        );

        if ($collectionPointsCount !== null) {
            $volVector = sprintf(
                "Coleção com %s vetores indexados\ntop_k=%d • min_score=%s\n%d resultado(s) após filtro",
                $this->formatIntPtBr($collectionPointsCount),
                $topK,
                $this->formatDecimalPtBr($minScore),
                $hits
            );
        } else {
            $volVector = sprintf(
                "Busca vetorial\ntop_k=%d • min_score=%s\n%d resultado(s)\n(coleção indisponível)",
                $topK,
                $this->formatDecimalPtBr($minScore),
                $hits
            );
        }

        $volLlm = sprintf(
            "%s tokens estimados na resposta\n%s tokens no prompt completo\n(entrada ao modelo)",
            $this->formatIntPtBr($answerTokens),
            $this->formatIntPtBr($promptTokens)
        );

        $costEmbed = sprintf(
            "%s estimado nesta requisição\n(taxa ref.: %s USD / 1k tokens — embeddings)",
            $this->formatUsdPtBr($costs['embedding']),
            number_format((float) $pricing['embedding_usd_per_1k_tokens'], 5, ',', '.')
        );

        $costVector = sprintf(
            "%s na API OpenAI\n(buscas no Qdrant: sem cobrança de API)",
            $this->formatUsdPtBr($costs['search'])
        );

        $inLlm = (float) ($costs['llm_input'] ?? 0.0);
        $outLlm = (float) ($costs['llm_output'] ?? 0.0);
        $costLlm = sprintf(
            "%s total estimado\nentrada: %s • saída: %s\n(ref.: %s USD/1M in + %s USD/1M out — gpt-4o)",
            $this->formatUsdPtBr($costs['generation']),
            $this->formatUsdPtBr($inLlm),
            $this->formatUsdPtBr($outLlm),
            number_format((float) $pricing['gpt4o_input_usd_per_1m'], 2, ',', '.'),
            number_format((float) $pricing['gpt4o_output_usd_per_1m'], 2, ',', '.')
        );

        return [
            'title' => $c['title'],
            'rows' => [
                [
                    'component' => $c['labels']['embedding'],
                    'volume' => $volEmbed,
                    'cost' => $costEmbed,
                    'time_ms' => $timings['embedding'],
                ],
                [
                    'component' => $c['labels']['vector'],
                    'volume' => $volVector,
                    'cost' => $costVector,
                    'time_ms' => $timings['search'],
                ],
                [
                    'component' => $c['labels']['llm'],
                    'volume' => $volLlm,
                    'cost' => $costLlm,
                    'time_ms' => $timings['generation'],
                ],
            ],
        ];
    }

    private function formatUsdPtBr(float $n): string
    {
        return '$ '.number_format($n, 6, ',', '.');
    }

    private function formatIntPtBr(int $n): string
    {
        return number_format($n, 0, ',', '.');
    }

    private function formatDecimalPtBr(float $n): string
    {
        return number_format($n, 2, ',', '.');
    }

    /**
     * @return array{embedding: float, search: float, generation: float, total: float, llm_input: float, llm_output: float}
     */
    private function calculateCosts(string $query, string $context, string $answer): array
    {
        $fullPrompt = $this->llmPromptForCost($query, $context);
        $embeddingCost = OpenAiCostEstimator::estimateEmbeddingUsdFromChars(strlen($query));
        $llm = OpenAiCostEstimator::estimateGpt4oUsdFromChars(strlen($fullPrompt), strlen($answer));
        $searchCost = 0.0;

        return [
            'embedding' => $embeddingCost,
            'search' => $searchCost,
            'generation' => $llm['total_usd'],
            'llm_input' => $llm['input_usd'],
            'llm_output' => $llm['output_usd'],
            'total' => $embeddingCost + $searchCost + $llm['total_usd'],
        ];
    }
}
