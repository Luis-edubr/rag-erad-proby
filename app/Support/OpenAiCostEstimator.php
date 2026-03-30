<?php

namespace App\Support;

/**
 * Estimativas de custo por requisição (USD) com base em contagem aproximada de tokens (chars/4).
 * Não substitui o uso da API de Usage da OpenAI; serve para relatórios e tabelas comparativas.
 */
final class OpenAiCostEstimator
{
    public static function estimateEmbeddingUsdFromChars(int $queryChars): float
    {
        $tokens = max(1, (int) ceil($queryChars / 4));

        return ($tokens / 1000.0) * (float) config('rag.pricing.embedding_usd_per_1k_tokens');
    }

    /**
     * @return array{input_usd: float, output_usd: float, total_usd: float}
     */
    public static function estimateGpt4oUsdFromChars(int $promptChars, int $outputChars): array
    {
        $inTokens = max(1, (int) ceil($promptChars / 4));
        $outTokens = max(1, (int) ceil($outputChars / 4));

        $inUsd = ($inTokens / 1_000_000) * (float) config('rag.pricing.gpt4o_input_usd_per_1m');
        $outUsd = ($outTokens / 1_000_000) * (float) config('rag.pricing.gpt4o_output_usd_per_1m');

        return [
            'input_usd' => $inUsd,
            'output_usd' => $outUsd,
            'total_usd' => $inUsd + $outUsd,
        ];
    }
}
