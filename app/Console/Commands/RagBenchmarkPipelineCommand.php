<?php

namespace App\Console\Commands;

use App\Services\RagPipelineMetricsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RagBenchmarkPipelineCommand extends Command
{
    protected $signature = 'rag:benchmark-pipeline
                            {--cache : Habilita RAG_CACHE (embedding + resultados de busca + respostas em cache)}
                            {--both : Executa cenário sem e com cache e grava dois JSON + tabela Markdown}
                            {--top-k=10 : top_k da busca}
                            {--min-score=0.75 : min_score da busca}';

    protected $description = 'Mede latência (embedding, Qdrant, LLM) e custo estimado por etapa; exporta JSON e opcionalmente METRICAS_PIPELINE.md';

    public function handle(RagPipelineMetricsService $pipeline): int
    {
        $queriesPath = base_path('datasets/rag-benchmark/queries.jsonl');

        if (! is_file($queriesPath)) {
            $this->error('Arquivo não encontrado: datasets/rag-benchmark/queries.jsonl');

            return self::FAILURE;
        }

        $topK = (int) $this->option('top-k');
        $minScore = (float) $this->option('min-score');

        if ($this->option('both')) {
            config(['app.rag_cache_enabled' => false]);
            $off = $this->runBenchmark($pipeline, $queriesPath, $topK, $minScore);
            $this->writeJson('pipeline_metrics_cache_off.json', $off);

            config(['app.rag_cache_enabled' => true]);
            $on = $this->runBenchmark($pipeline, $queriesPath, $topK, $minScore);
            $this->writeJson('pipeline_metrics_cache_on.json', $on);

            $this->writeMarkdown($off, $on);
            $this->info('Arquivos em results/baseline/: pipeline_metrics_cache_off.json, pipeline_metrics_cache_on.json, METRICAS_PIPELINE.md');

            return self::SUCCESS;
        }

        config(['app.rag_cache_enabled' => (bool) $this->option('cache')]);
        $data = $this->runBenchmark($pipeline, $queriesPath, $topK, $minScore);
        $suffix = config('app.rag_cache_enabled') ? 'cache_on' : 'cache_off';
        $this->writeJson("pipeline_metrics_{$suffix}.json", $data);

        $this->info("Gravado: results/baseline/pipeline_metrics_{$suffix}.json");
        $this->table(
            ['Etapa', 'P50 (ms)', 'P95 (ms)', 'Custo médio/query (USD)'],
            [
                ['Embedding', number_format($data['stages']['embedding']['p50_ms'], 2), number_format($data['stages']['embedding']['p95_ms'], 2), number_format($data['stages']['embedding']['avg_cost_usd'], 6)],
                ['Busca vetorial (Qdrant)', number_format($data['stages']['vector_search']['p50_ms'], 2), number_format($data['stages']['vector_search']['p95_ms'], 2), number_format($data['stages']['vector_search']['avg_cost_usd'], 6)],
                ['Geração LLM (gpt-4o)', number_format($data['stages']['llm']['p50_ms'], 2), number_format($data['stages']['llm']['p95_ms'], 2), number_format($data['stages']['llm']['avg_cost_usd'], 6)],
                ['Total pipeline', number_format($data['total']['p50_ms'], 2), number_format($data['total']['p95_ms'], 2), number_format($data['total']['avg_cost_usd'], 6)],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function runBenchmark(RagPipelineMetricsService $pipeline, string $queriesPath, int $topK, float $minScore): array
    {
        $handle = fopen($queriesPath, 'r');
        $rows = [];

        while ($line = fgets($handle)) {
            $queryData = json_decode(trim($line), true);
            if (! $queryData || ! isset($queryData['query'])) {
                continue;
            }

            $query = $queryData['query'];
            $this->output->write('.');

            try {
                $out = $pipeline->answerWithStageMetrics($query, $topK, $minScore);
                $rows[] = [
                    'metrics' => $out['metrics'],
                    'cost' => $out['cost'],
                ];
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn('Erro na query: '.$e->getMessage());
            }
        }

        fclose($handle);
        $this->newLine();

        $n = count($rows);
        if ($n === 0) {
            return $this->emptyPayload();
        }

        $emb = array_column(array_column($rows, 'metrics'), 'embedding_ms');
        $vec = array_column(array_column($rows, 'metrics'), 'vector_search_ms');
        $llm = array_column(array_column($rows, 'metrics'), 'llm_ms');
        $tot = array_column(array_column($rows, 'metrics'), 'total_ms');

        $ce = array_column(array_column($rows, 'cost'), 'embedding_usd');
        $cv = array_column(array_column($rows, 'cost'), 'vector_usd');
        $cl = array_column(array_column($rows, 'cost'), 'llm_usd');
        $ct = array_column(array_column($rows, 'cost'), 'total_usd');

        sort($emb);
        sort($vec);
        sort($llm);
        sort($tot);

        return [
            'generated_at' => now()->toIso8601String(),
            'cache_enabled' => config('app.rag_cache_enabled'),
            'queries_total' => $n,
            'top_k' => $topK,
            'min_score' => $minScore,
            'stages' => [
                'embedding' => [
                    'p50_ms' => $this->percentile($emb, 0.50),
                    'p95_ms' => $this->percentile($emb, 0.95),
                    'p99_ms' => $this->percentile($emb, 0.99),
                    'avg_cost_usd' => array_sum($ce) / $n,
                ],
                'vector_search' => [
                    'p50_ms' => $this->percentile($vec, 0.50),
                    'p95_ms' => $this->percentile($vec, 0.95),
                    'p99_ms' => $this->percentile($vec, 0.99),
                    'avg_cost_usd' => array_sum($cv) / $n,
                ],
                'llm' => [
                    'p50_ms' => $this->percentile($llm, 0.50),
                    'p95_ms' => $this->percentile($llm, 0.95),
                    'p99_ms' => $this->percentile($llm, 0.99),
                    'avg_cost_usd' => array_sum($cl) / $n,
                ],
            ],
            'total' => [
                'p50_ms' => $this->percentile($tot, 0.50),
                'p95_ms' => $this->percentile($tot, 0.95),
                'p99_ms' => $this->percentile($tot, 0.99),
                'avg_cost_usd' => array_sum($ct) / $n,
            ],
        ];
    }

    /**
     * @param  array<int, float>  $sorted
     */
    private function percentile(array $sorted, float $p): float
    {
        $count = count($sorted);
        if ($count === 0) {
            return 0.0;
        }
        $idx = (int) floor($count * $p);
        $idx = min($idx, $count - 1);

        return round($sorted[$idx], 4);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function writeJson(string $filename, array $data): void
    {
        $dir = base_path('results/baseline');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        File::put($dir.'/'.$filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param  array<string, mixed>  $off
     * @param  array<string, mixed>  $on
     */
    private function writeMarkdown(array $off, array $on): void
    {
        $ref = config('rag.reference_baseline');
        $pricingNote = sprintf(
            'Preços referência: embedding %.5f USD/1k tokens; gpt-4o %.2f/1M in + %.2f/1M out (config/rag.php).',
            config('rag.pricing.embedding_usd_per_1k_tokens'),
            config('rag.pricing.gpt4o_input_usd_per_1m'),
            config('rag.pricing.gpt4o_output_usd_per_1m')
        );

        $lines = [];
        $lines[] = '# Métricas do pipeline RAG (projeto rag-erad-proby)';
        $lines[] = '';
        $lines[] = 'Valores **medidos** por `php artisan rag:benchmark-pipeline --both` (latências reais por etapa). Custos são **estimativas** por contagem aproximada de tokens (chars/4), exceto Qdrant (custo infra próprio ≈ **0 USD** na API OpenAI).';
        $lines[] = '';
        $lines[] = $pricingNote;
        $lines[] = '';
        $lines[] = '### Por que este desenho tende a ser mais eficiente';
        $lines[] = '- **Recuperação vetorial** restringe o contexto enviado ao LLM a trechos relevantes, em geral **menor** que enviar toda a base ou prompts enormes sem RAG.';
        $lines[] = '- **Embeddings (ada-002)** custam muito menos por token que **geração (gpt-4o)**; separar etapas permite cachear embeddings e resultados de busca para consultas repetidas.';
        $lines[] = '- **Baseline de referência** na última linha é uma ordem de grandeza para “só LLM”; substitua por medições suas se precisar de rigor comparativo.';
        $lines[] = '';
        $lines[] = '### Tabela comparativa (P50)';
        $lines[] = '';
        $lines[] = '| Cenário | Embedding (ms) | Busca vetorial (ms) | LLM (ms) | Total (ms) | $ embedding | $ busca* | $ LLM | $ total |';
        $lines[] = '|---------|----------------|---------------------|----------|------------|-------------|---------|-------|---------|';

        $lines[] = $this->mdRow('Cache **OFF** (medido)', $off);
        $lines[] = $this->mdRow('Cache **ON** (medido)', $on);
        $lines[] = sprintf(
            '| %s | — | — | — | **%d** | — | — | — | **%.4f** |',
            str_replace('|', '\\|', $ref['label']),
            $ref['typical_latency_ms'],
            $ref['typical_cost_usd']
        );

        $lines[] = '';
        $lines[] = '\\* Busca em Qdrant auto-hospedado: custo API OpenAI **0**; infraestrutura própria não entra nesta coluna.';
        $lines[] = '';
        $lines[] = '### Observação sobre cache';
        $lines[] = 'Com **consultas todas distintas** no benchmark, o ganho de cache pode ser pequeno (cache por texto de query). Repetindo a mesma pergunta, embedding + resultados de busca + resposta podem ser reutilizados — aí o modo **cache ON** mostra ganho maior de latência e custo.';
        $lines[] = '';
        $lines[] = '_Gerado automaticamente. Regenerar: `php artisan rag:benchmark-pipeline --both`_';
        $lines[] = '';

        File::put(base_path('results/baseline/METRICAS_PIPELINE.md'), implode("\n", $lines));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function mdRow(string $label, array $payload): string
    {
        if (($payload['queries_total'] ?? 0) === 0) {
            return sprintf('| %s | — | — | — | — | — | — | — | — |', $label);
        }

        $s = $payload['stages'];

        return sprintf(
            '| %s | %.2f | %.2f | %.2f | %.2f | %.6f | %.6f | %.6f | %.6f |',
            str_replace('|', '\\|', $label),
            $s['embedding']['p50_ms'],
            $s['vector_search']['p50_ms'],
            $s['llm']['p50_ms'],
            $payload['total']['p50_ms'],
            $s['embedding']['avg_cost_usd'],
            $s['vector_search']['avg_cost_usd'],
            $s['llm']['avg_cost_usd'],
            $payload['total']['avg_cost_usd']
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(): array
    {
        return [
            'queries_total' => 0,
            'stages' => [
                'embedding' => ['p50_ms' => 0, 'p95_ms' => 0, 'p99_ms' => 0, 'avg_cost_usd' => 0],
                'vector_search' => ['p50_ms' => 0, 'p95_ms' => 0, 'p99_ms' => 0, 'avg_cost_usd' => 0],
                'llm' => ['p50_ms' => 0, 'p95_ms' => 0, 'p99_ms' => 0, 'avg_cost_usd' => 0],
            ],
            'total' => ['p50_ms' => 0, 'p95_ms' => 0, 'p99_ms' => 0, 'avg_cost_usd' => 0],
        ];
    }
}
