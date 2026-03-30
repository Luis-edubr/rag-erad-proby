# Protocolo de Benchmark RAG

## Objetivo
Estabelecer um protocolo reproduzível para benchmarking do sistema RAG em dois cenários: com e sem cache.

## Configuração de Ambiente

### Hardware Alvo
- Servidor: Hostinger + VPN (equivalente)
- Vetor DB: Qdrant Cloud (AWS sa-east-1)
- Region: sa-east-1
- Node: 1
- vCPU: 0.5
- RAM: 1 GiB
- Disk: 4 GiB

### Modelos Fixos
- LLM: gpt-4o
- Embedding: text-embedding-ada-002
- OCR Fallback: gpt-4o

## Cenários de Benchmark

### Cenário A — Sem Cache
- `RAG_CACHE_ENABLED=false`
- Embeddings: nenhum cache
- Busca Vetorial: nenhum cache
- Respostas: nenhum cache
- Objetivo: medir latência bruta do pipeline

### Cenário B — Com Cache
- `RAG_CACHE_ENABLED=true`
- Embeddings: cache por 60 minutos
- Busca Vetorial: cache por 60 minutos
- Respostas: cache por 60 minutos
- Objetivo: medir impacto de cache em latência

## Protocolo de Execução

### Limpeza de Estado Entre Cenários
```bash
# Limpar cache
php artisan cache:clear

# Limpar registros de benchmark
php artisan tinker
>>> DB::table('benchmark_runs')->truncate();
>>> exit;
```

### Warmup
- **Queries**: 30 queries de aquecimento
- **Objetivo**: estabilizar pipeline e aguardar inicializações de conexão
- **Métrica**: não inclusa em resultados finais

### Rodada Oficial
- **Repetições**: 5 repetições completas
- **Queries por rodada**: 5 queries do dataset
- **Mesma ordem**: mesma ordem em todas as rodadas
- **Concorrência**: 1 (sequencial)

### Variações de Parâmetros
- **top_k**: [5, 10]
- **min_score**: [0.75]
- **Combinações**: 2 valores de top_k × 1 valor de min_score = 2 combinações

## Métricas

### Latência (em milissegundos)
- **p50**: mediana
- **p95**: percentil 95%
- **p99**: percentil 99%

### Qualidade (Retrieval)
- **Recall@K**: proporção de documentos relevantes recuperados
- **NDCG@10**: Normalized Discounted Cumulative Gain@10
- **MRR**: Mean Reciprocal Rank

### Custo
- **USD/query**: custo total de tokens (embedding + geração)

### Por Componente
- **embedding_latency_ms**: tempo do embedding
- **vector_search_latency_ms**: tempo da busca no Qdrant
- **llm_generation_latency_ms**: tempo da geração de resposta
- **pipeline_total_latency_ms**: soma total

### Compar ação A/B
- **cache_hit_rate**: proporção de hits em cada componente (Cenário B)
- **delta_percentual**: diferença percentual entre cenários

## Dataset

### Documentos
- Localização: `datasets/rag-benchmark/documents/`
- Formato: .txt
- Quantidade: 2 documentos de exemplo
- Conteúdo: Machine Learning, Deep Learning, Neural Networks

### Queries
- Localização: `datasets/rag-benchmark/queries.jsonl`
- Formato: JSONL (1 query por linha)
- Quantidade: 5 queries de exemplo
- Estrutura: {"id": int, "query": string}

### Relevância (Ground Truth)
- Localização: `datasets/rag-benchmark/qrels.tsv`
- Formato: TSV (tab-separated values)
- Colunas: query_id, document_id, relevance (0 ou 1)

## Saídas

### Arquivos de Resultados
```
results/baseline/
├── metrics.csv                    # Resumo de métricas por cenário
├── latency.json                   # Distribuição de latências
├── quality.json                   # Métricas de qualidade
├── cost.json                      # Análise de custo
├── cache_comparison.csv           # Comparação A/B em tabela
└── cache_comparison.json          # Comparação A/B em JSON
```

### Formato de metrics.csv
```
scenario,top_k,min_score,queries_total,latency_p50_ms,latency_p95_ms,latency_p99_ms,recall_at_k,ndcg_at_10,mrr,cost_usd
```

## Checklist de Reprodução

- [ ] Copiar `.env.example` para `.env`
- [ ] Preencher `OPENAI_API_KEY`, `QDRANT_URL`, `QDRANT_API_KEY`
- [ ] Executar `bash scripts/setup.sh`
- [ ] Validar conectividade com `php artisan qdrant:ensure-collection`
- [ ] Executar `bash scripts/benchmark/run_all.sh`
- [ ] Verificar saídas em `results/baseline/`
- [ ] Validar que os arquivos CSV/JSON foram gerados

## Notas de Fidelidade ao ProbY

1. Modelos fixos em `gpt-4o` para geração e `text-embedding-ada-002` para embeddings
2. Sem alternância dinâmica de modelos
3. Métrica de distância: Cosine no Qdrant
4. Namespace de cache: `rag_exp:{component}:{hash}`
5. TTL de cache: 60 minutos
