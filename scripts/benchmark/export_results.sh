#!/bin/bash

set -e

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$PROJECT_ROOT"
RESULTS_DIR="$PROJECT_ROOT/results/baseline"

mkdir -p "$RESULTS_DIR"

echo "Exporting benchmark results..."

php artisan tinker <<'EOF'
use App\Models\BenchmarkRun;
use Illuminate\Support\Facades\File;

$benchmarks = BenchmarkRun::orderBy('created_at')->get();

$csv = "scenario,top_k,min_score,queries_total,latency_p50_ms,latency_p95_ms,latency_p99_ms,recall_at_k,ndcg_at_10,mrr,cost_usd\n";

foreach ($benchmarks as $run) {
    $csv .= implode(',', [
        $run->scenario,
        $run->top_k,
        $run->min_score,
        $run->queries_total,
        $run->latency_p50_ms,
        $run->latency_p95_ms,
        $run->latency_p99_ms,
        $run->recall_at_k ?? '',
        $run->ndcg_at_10 ?? '',
        $run->mrr ?? '',
        $run->cost_usd ?? '',
    ]) . "\n";
}

File::put(base_path('results/baseline/metrics.csv'), $csv);

$json = $benchmarks->map(function ($run) {
    return [
        'scenario' => $run->scenario,
        'top_k' => $run->top_k,
        'min_score' => $run->min_score,
        'queries_total' => $run->queries_total,
        'latency' => [
            'p50_ms' => $run->latency_p50_ms,
            'p95_ms' => $run->latency_p95_ms,
            'p99_ms' => $run->latency_p99_ms,
        ],
        'quality' => [
            'recall_at_k' => $run->recall_at_k,
            'ndcg_at_10' => $run->ndcg_at_10,
            'mrr' => $run->mrr,
        ],
        'cost_usd' => $run->cost_usd,
        'created_at' => $run->created_at->toIso8601String(),
    ];
})->toArray();

File::put(base_path('results/baseline/latency.json'), json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

if ($benchmarks->count() >= 2) {
    $noCacheRun = $benchmarks->where('scenario', 'baseline_no_cache')->first();
    $withCacheRun = $benchmarks->where('scenario', 'baseline_with_cache')->first();
    
    if ($noCacheRun && $withCacheRun) {
        $deltaP50 = (($noCacheRun->latency_p50_ms - $withCacheRun->latency_p50_ms) / $noCacheRun->latency_p50_ms) * 100;
        $deltaP95 = (($noCacheRun->latency_p95_ms - $withCacheRun->latency_p95_ms) / $noCacheRun->latency_p95_ms) * 100;
        $deltaP99 = (($noCacheRun->latency_p99_ms - $withCacheRun->latency_p99_ms) / $noCacheRun->latency_p99_ms) * 100;
        
        $comparison = [
            'baseline_no_cache' => [
                'latency_p50_ms' => $noCacheRun->latency_p50_ms,
                'latency_p95_ms' => $noCacheRun->latency_p95_ms,
                'latency_p99_ms' => $noCacheRun->latency_p99_ms,
            ],
            'baseline_with_cache' => [
                'latency_p50_ms' => $withCacheRun->latency_p50_ms,
                'latency_p95_ms' => $withCacheRun->latency_p95_ms,
                'latency_p99_ms' => $withCacheRun->latency_p99_ms,
            ],
            'delta_percentual' => [
                'p50_percent' => $deltaP50,
                'p95_percent' => $deltaP95,
                'p99_percent' => $deltaP99,
            ],
        ];
        
        File::put(base_path('results/baseline/cache_comparison.json'), json_encode($comparison, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        $comparisonCsv = "metric,no_cache,with_cache,delta_percent\n";
        $comparisonCsv .= "latency_p50_ms," . $noCacheRun->latency_p50_ms . "," . $withCacheRun->latency_p50_ms . "," . $deltaP50 . "\n";
        $comparisonCsv .= "latency_p95_ms," . $noCacheRun->latency_p95_ms . "," . $withCacheRun->latency_p95_ms . "," . $deltaP95 . "\n";
        $comparisonCsv .= "latency_p99_ms," . $noCacheRun->latency_p99_ms . "," . $withCacheRun->latency_p99_ms . "," . $deltaP99 . "\n";
        
        File::put(base_path('results/baseline/cache_comparison.csv'), $comparisonCsv);
    }
}

echo "✓ Results exported to results/baseline/\n";
EOF

echo ""
echo "Generated files:"
ls -lh "$RESULTS_DIR"
