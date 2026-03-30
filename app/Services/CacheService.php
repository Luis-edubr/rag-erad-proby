<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    private const TTL_MINUTES = 60;

    private const NAMESPACE = 'rag_exp';

    public function getEmbedding(string $query): ?array
    {
        if (! $this->cacheEnabled()) {
            return null;
        }

        $key = $this->getCacheKey('embedding', hash('sha256', strtolower($query)));

        return Cache::get($key);
    }

    public function putEmbedding(string $query, array $embedding): void
    {
        if (! $this->cacheEnabled()) {
            return;
        }

        $key = $this->getCacheKey('embedding', hash('sha256', strtolower($query)));
        Cache::put($key, $embedding, now()->addMinutes(self::TTL_MINUTES));
    }

    public function getSearchResults(string $embeddingHash, int $topK, float $minScore): ?array
    {
        if (! $this->cacheEnabled()) {
            return null;
        }

        $cacheKey = hash('sha256', "{$embeddingHash}|{$topK}|{$minScore}");
        $key = $this->getCacheKey('search', $cacheKey);

        return Cache::get($key);
    }

    public function putSearchResults(string $embeddingHash, int $topK, float $minScore, array $results): void
    {
        if (! $this->cacheEnabled()) {
            return;
        }

        $cacheKey = hash('sha256', "{$embeddingHash}|{$topK}|{$minScore}");
        $key = $this->getCacheKey('search', $cacheKey);
        Cache::put($key, $results, now()->addMinutes(self::TTL_MINUTES));
    }

    public function getAnswer(string $prompt): ?string
    {
        if (! $this->cacheEnabled()) {
            return null;
        }

        $key = $this->getCacheKey('answer', hash('sha256', $prompt));

        return Cache::get($key);
    }

    public function putAnswer(string $prompt, string $answer): void
    {
        if (! $this->cacheEnabled()) {
            return;
        }

        $key = $this->getCacheKey('answer', hash('sha256', $prompt));
        Cache::put($key, $answer, now()->addMinutes(self::TTL_MINUTES));
    }

    public function isEnabled(): bool
    {
        return $this->cacheEnabled();
    }

    public function getStatus(): array
    {
        return [
            'cache_enabled' => $this->cacheEnabled(),
            'driver' => config('cache.default'),
        ];
    }

    private function cacheEnabled(): bool
    {
        return config('app.rag_cache_enabled', false);
    }

    private function getCacheKey(string $component, string $hash): string
    {
        return "{self::NAMESPACE}:{$component}:{$hash}";
    }
}
