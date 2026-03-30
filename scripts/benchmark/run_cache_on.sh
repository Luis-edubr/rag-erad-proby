#!/bin/bash

set -e

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$PROJECT_ROOT"
RESULTS_DIR="$PROJECT_ROOT/results/baseline"

mkdir -p "$RESULTS_DIR"

echo "Running queries with cache..."

php artisan tinker <<'EOF'
use App\Models\BenchmarkRun;
use App\Services\DocumentVectorizationService;

$vectorizationService = app(DocumentVectorizationService::class);
$queriesPath = base_path('datasets/rag-benchmark/queries.jsonl');
$latencies = [];

putenv('RAG_CACHE_ENABLED=true');

if (is_file($queriesPath)) {
    $handle = fopen($queriesPath, 'r');
    
    while ($line = fgets($handle)) {
        $queryData = json_decode(trim($line), true);
        
        if (!$queryData || !isset($queryData['query'])) {
            continue;
        }
        
        $query = $queryData['query'];
        $startTime = microtime(true);
        
        try {
            $results = $vectorizationService->searchDocuments($query, 10, 0.75);
        } catch (\Exception $e) {
            echo "Error processing query: " . $e->getMessage() . "\n";
            continue;
        }
        
        $elapsed = (microtime(true) - $startTime) * 1000;
        $latencies[] = $elapsed;
        
        echo ".";
    }
    
    fclose($handle);
}

if (!empty($latencies)) {
    sort($latencies);
    $count = count($latencies);
    $p50 = $latencies[floor($count * 0.50)];
    $p95 = $latencies[floor($count * 0.95)];
    $p99 = $latencies[floor($count * 0.99)];
    
    BenchmarkRun::create([
        'scenario' => 'baseline_with_cache',
        'top_k' => 10,
        'min_score' => 0.75,
        'queries_total' => $count,
        'latency_p50_ms' => $p50,
        'latency_p95_ms' => $p95,
        'latency_p99_ms' => $p99,
    ]);
    
    echo "\n✓ Benchmark with cache completed\n";
    echo "  Queries: $count\n";
    echo "  P50: {$p50}ms\n";
    echo "  P95: {$p95}ms\n";
    echo "  P99: {$p99}ms\n";
}
EOF
