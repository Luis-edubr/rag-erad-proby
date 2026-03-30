<?php

return [

    /*
    | Estimativas de preço (USD) para relatórios — alinhe com a página de preços OpenAI.
    | text-embedding-ada-002 e gpt-4o: valores típicos por token.
    */
    'pricing' => [
        'embedding_usd_per_1k_tokens' => (float) env('RAG_EMBEDDING_USD_PER_1K', 0.0001),
        'gpt4o_input_usd_per_1m' => (float) env('RAG_GPT4O_INPUT_USD_PER_1M', 2.50),
        'gpt4o_output_usd_per_1m' => (float) env('RAG_GPT4O_OUTPUT_USD_PER_1M', 10.00),
    ],

    /*
    | Referência para comparação qualitativa (não vem do benchmark automático).
    | Use para contextualizar ganhos do RAG vs. uma única chamada grande ao LLM.
    */
    'reference_baseline' => [
        'label' => 'Baseline típico: só LLM (1× gpt-4o, prompt longo sem recuperação)',
        'typical_latency_ms' => (int) env('RAG_REF_LLM_ONLY_MS', 4000),
        'typical_cost_usd' => (float) env('RAG_REF_LLM_ONLY_USD', 0.02),
        'note' => 'Ordem de grandeza para cenários com contexto extenso no prompt; substitua por medições suas se tiver.',
    ],

    /*
    | Textos da Tabela 1 do artigo (componente, volume, custo unitário). O tempo vem da medição por requisição.
    */
    'article_table' => [
        'title' => env('RAG_ARTICLE_TABLE_TITLE', 'Tabela 1. Métricas de performance e custo por componente da arquitetura RAG'),
        'labels' => [
            'embedding' => 'Embedding latency',
            'vector' => 'Vector search latency',
            'llm' => 'LLM generation latency',
        ],
        'cost_embedding' => env('RAG_ARTICLE_COST_EMBEDDING', 'US$ 0,10 / 1M tokens'),
        'cost_vector' => env('RAG_ARTICLE_COST_VECTOR', 'US$ 0,10 / 1M vetores'),
        'cost_llm' => env('RAG_ARTICLE_COST_LLM', 'US$ 10,00 / 1M tokens'),
        'collection_vector_count' => (int) env('RAG_ARTICLE_COLLECTION_VECTORS', 5_000_000),
        'vector_volume_template' => 'Busca em coleção de %s de vetores',
        'embedding_volume_template' => '%d tokens por query',
        'llm_volume_template' => '%d tokens gerados por resposta',
        'default_query_tokens' => (int) env('RAG_ARTICLE_DEFAULT_QUERY_TOKENS', 20),
        'default_llm_output_tokens' => (int) env('RAG_ARTICLE_DEFAULT_LLM_TOKENS', 500),
    ],

];
