<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class BaselineMetricsReader
{
    private const BASELINE_DIR = 'results/baseline';

    /**
     * Lê os JSON gerados por `rag:benchmark-pipeline` e dados opcionais de comparação de cache.
     *
     * @return array{
     *     pipeline_off: array|null,
     *     pipeline_on: array|null,
     *     has_pipeline: bool,
     *     cache_comparison: array|null,
     *     generated_at_off: string|null,
     *     generated_at_on: string|null
     * }
     */
    public function getPayload(): array
    {
        $root = base_path(self::BASELINE_DIR);
        $off = $this->readJson("{$root}/pipeline_metrics_cache_off.json");
        $on = $this->readJson("{$root}/pipeline_metrics_cache_on.json");
        $cacheComparison = $this->readJson("{$root}/cache_comparison.json");

        return [
            'pipeline_off' => $off,
            'pipeline_on' => $on,
            'has_pipeline' => $off !== null && $on !== null,
            'cache_comparison' => $cacheComparison,
            'generated_at_off' => $off['generated_at'] ?? null,
            'generated_at_on' => $on['generated_at'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readJson(string $path): ?array
    {
        if (! File::isFile($path)) {
            return null;
        }

        $decoded = json_decode(File::get($path), true);

        return is_array($decoded) ? $decoded : null;
    }
}
