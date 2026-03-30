<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BenchmarkRun extends Model
{
    protected $fillable = [
        'scenario',
        'top_k',
        'min_score',
        'queries_total',
        'latency_p50_ms',
        'latency_p95_ms',
        'latency_p99_ms',
        'recall_at_k',
        'ndcg_at_10',
        'mrr',
        'cost_usd',
    ];
}
