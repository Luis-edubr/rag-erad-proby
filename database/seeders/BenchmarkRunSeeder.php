<?php

namespace Database\Seeders;

use App\Models\BenchmarkRun;
use Illuminate\Database\Seeder;

class BenchmarkRunSeeder extends Seeder
{
    public function run(): void
    {
        BenchmarkRun::create([
            'scenario' => 'baseline_no_cache',
            'top_k' => 10,
            'min_score' => 0.75,
            'queries_total' => 0,
            'latency_p50_ms' => 0.0,
            'latency_p95_ms' => 0.0,
            'latency_p99_ms' => 0.0,
            'recall_at_k' => null,
            'ndcg_at_10' => null,
            'mrr' => null,
            'cost_usd' => null,
        ]);

        BenchmarkRun::create([
            'scenario' => 'baseline_with_cache',
            'top_k' => 10,
            'min_score' => 0.75,
            'queries_total' => 0,
            'latency_p50_ms' => 0.0,
            'latency_p95_ms' => 0.0,
            'latency_p99_ms' => 0.0,
            'recall_at_k' => null,
            'ndcg_at_10' => null,
            'mrr' => null,
            'cost_usd' => null,
        ]);
    }
}
