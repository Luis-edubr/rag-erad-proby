<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('benchmark_runs', function (Blueprint $table) {
            $table->id();
            $table->string('scenario');
            $table->integer('top_k');
            $table->float('min_score');
            $table->integer('queries_total');
            $table->float('latency_p50_ms');
            $table->float('latency_p95_ms');
            $table->float('latency_p99_ms');
            $table->float('recall_at_k')->nullable();
            $table->float('ndcg_at_10')->nullable();
            $table->float('mrr')->nullable();
            $table->float('cost_usd')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benchmark_runs');
    }
};
