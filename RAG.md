# Plano de Feature — RAG Reproduzível (ProbY baseline) para Laravel limpo

## Objetivo
Implementar, em um repositório Laravel limpo, um pipeline RAG reproduzível com a mesma base técnica usada no ProbY, permitindo que qualquer pessoa execute comandos de setup, indexe documentos e rode benchmarks.

## Escopo obrigatório (fidelidade ao ProbY)
- **LLM principal (geração de query e resposta):** `gpt-4o` via endpoint `POST /v1/chat/completions`
- **Embedding model:** `text-embedding-ada-002` via endpoint `POST /v1/embeddings`
- **Fallback OCR para PDF quando parser falhar:** `gpt-4o` via endpoint `POST /v1/chat/completions`
- **Qdrant Cloud:** coleção única `rag_experiment`, dimensão `1536`, distância `Cosine`
- **Sem alternância de modelos:** não criar feature flag, dropdown, env switch, ou estratégia de seleção de modelo

> Nota de fidelidade: no código do ProbY existe log com string “gpt-4o-mini” no fallback de PDF, mas a chamada HTTP efetiva usa `gpt-4o`. Para replicação técnica fiel de execução, usar `gpt-4o` no request.

## Infra-alvo para benchmark (igual estudo)
- Aplicação: servidor (equivalente ao uso em Hostinger + VPN)
- Vetor DB: Qdrant Cloud
  - Provider: AWS
  - Region: `sa-east-1`
  - Node: `1`
  - vCPU: `0.5`
  - RAM: `1 GiB`
  - Disk: `4 GiB`

---

## Fase 0 — Bootstrap do projeto

### 0.1 Criar projeto e dependências
Comandos:
```bash
composer create-project laravel/laravel rag-proby-repro
cd rag-proby-repro
composer require guzzlehttp/guzzle smalot/pdfparser
php artisan storage:link
```

### 0.2 Estrutura de pastas (mínima)
Criar:
- `app/Services/`
- `app/DataTransferObjects/`
- `app/Exceptions/`
- `app/Validators/`
- `app/Http/Controllers/Api/`
- `app/Http/Requests/`
- `database/migrations/`
- `scripts/benchmark/`
- `datasets/rag-benchmark/`
- `docs/benchmarking/`

### 0.3 Variáveis de ambiente
Adicionar ao `.env.example`:
```dotenv
OPENAI_API_KEY=
QDRANT_URL=
QDRANT_API_KEY=
QDRANT_PORT=6333
QDRANT_USE_TLS=true
QDRANT_VERIFY_SSL=true
```

Regras obrigatórias:
- `OPENAI_API_KEY` deve ser preenchida manualmente antes de qualquer execução.
- O script de setup deve falhar com mensagem clara se `OPENAI_API_KEY` estiver vazia.
- Nunca commitar `.env` com chave real.

---

## Fase 1 — Configuração de Qdrant (idêntica ao baseline)

### 1.1 Criar `config/qdrant.php`
Parâmetros fixos:
- `collection_name`: `rag_experiment`
- `vector_dimension`: `1536`
- `distance_metric`: `Cosine`
- `timeout`: `60`
- `connect_timeout`: `10`

### 1.2 Implementar `App\Services\QdrantService`
Métodos obrigatórios:
1. `ensureCollectionExists()`
2. `createCollection()`
3. `upsertPoints(array $points)`
4. `searchByVector(array $vector, int $limit = 10)`
5. `scrollPoints(...)`
6. `deletePoints(...)`
7. `deletePointsByFilter(...)`

### 1.3 Configuração de coleção
No `createCollection()` enviar:
- `vectors.size = 1536`
- `vectors.distance = Cosine`
- `optimizers_config.indexing_threshold = 10000`

Aceite:
- Subir app e validar que `GET /collections/rag_experiment` responde 200.
- Se não existir, criação automática deve funcionar.

---

## Fase 2 — Ingestão de documentos e vetorização

### 2.1 Validação de upload
Criar `DocumentVectorizationRequest` com regras:
- MIME suportado: `text/plain`, `application/pdf`
- Máximo: `10MB`
- Campos: `file` obrigatório, `uploaded_by` opcional

### 2.2 Extração de texto
Criar `DocumentTextExtractorService` com fluxo:
1. TXT: `file_get_contents`
2. PDF: `smalot/pdfparser`
3. Fallback quando parser falhar: OCR via OpenAI `gpt-4o`

### 2.3 Embeddings e chunking
Criar `ChatGPTServiceV2` com constantes fixas:
- `model (generation) = gpt-4o`
- `EMBEDDING_MODEL = text-embedding-ada-002`
- `EMBEDDING_ENDPOINT = https://api.openai.com/v1/embeddings`
- `MAX_TOKENS_PER_CHUNK = 2000`
- `CHUNK_OVERLAP_TOKENS = 200`

Regras do chunking:
- Estimativa de tokens: `ceil(strlen(text)/4)`
- Sobreposição de contexto
- Split por sentença com fallback por palavras

Batch embeddings:
- tamanho de lote `100`
- delay entre lotes `100ms`
- retry simples em 429

### 2.4 DTO de ponto vetorial
Criar `DocumentEmbeddingData` para montar ponto Qdrant com payload:
- `filename`
- `chunk_index`
- `chunk_text`
- `upload_date`
- `metadata` (opcional)

Aceite:
- Upload de um PDF/TXT gera `N` chunks e `N` pontos no Qdrant.
- Vetor de cada chunk tem dimensão 1536.

---

## Fase 3 — Busca semântica e endpoint RAG

### 3.1 Serviço de busca
Criar `DocumentVectorizationService::searchDocuments()`
Fluxo:
1. Embedding da query com `text-embedding-ada-002`
2. Busca sem filtro por tenant
3. `topK` padrão `10`
4. filtro por score mínimo (`minScore` recebido)

### 3.2 Endpoint de busca
Criar endpoint API:
- `POST /api/rag/search`
Body:
```json
{
  "query": "texto de busca",
  "top_k": 10,
  "min_score": 0.75
}
```
Resposta:
- lista de chunks com `score`, `document_name`, `chunk_index`, `text`

### 3.3 Endpoint de answer RAG
Criar endpoint API:
- `POST /api/rag/answer`
Fluxo:
1. recuperar chunks por similaridade
2. montar contexto concatenado
3. chamar `gpt-4o` (`/v1/chat/completions`) para resposta final

Aceite:
- Para query conhecida, retorno contém chunks relevantes e resposta textual baseada no contexto.

---

## Fase 4 — Persistência mínima para reprodução

### 4.1 Migrations mínimas
Criar tabelas:
1. `documents`
  - `id`, `file_name`, `original_name`, `file_type`, `file_size`, `file_path`, timestamps
2. `benchmark_runs`
   - `id`, `scenario`, `top_k`, `min_score`, `queries_total`, `latency_p50_ms`, `latency_p95_ms`, `latency_p99_ms`, `recall_at_k`, `ndcg_at_10`, `mrr`, `cost_usd`, timestamps

### 4.2 Seed de dataset de benchmark
- Carregar documentos-base em `datasets/rag-benchmark/documents/`
- Carregar queries em `datasets/rag-benchmark/queries.jsonl`
- Carregar relevância em `datasets/rag-benchmark/qrels.tsv`

Aceite:
- `php artisan migrate --seed` deixa o sistema pronto para benchmark sem intervenção manual.

---

## Fase 5 — Benchmark reproduzível (obrigatório para paper)

### 5.0 Cenários A/B (com e sem cache)
Executar benchmark em dois cenários obrigatórios e comparáveis:
- **Cenário A — sem cache:** desabilitar cache de embeddings, cache de resultados de busca vetorial e cache de resposta final.
- **Cenário B — com cache:** habilitar os três caches com política idêntica entre rodadas.

Controles obrigatórios:
- Mesmo dataset, mesmas queries, mesma ordem de execução por rodada.
- Mesmo `top_k`, `min_score`, concorrência e infraestrutura.
- Limpar estado entre cenários (`cache:clear` + reset de chaves namespace do experimento).

Implementação sugerida:
- Variável de ambiente `RAG_CACHE_ENABLED=true|false`.
- Namespace de chave: `rag_exp:{component}:{hash}`.
- Componentes cacheáveis:
  1. embedding de query (`sha256(query_normalized)`),
  2. resultado de busca vetorial (`sha256(query_embedding+top_k+min_score)`),
  3. resposta final (`sha256(prompt_final_modelo)`).

### 5.1 Protocolo fixo
Definir em `docs/benchmarking/protocolo.md`:
- warmup: 30 queries
- rodada oficial: 5 repetições
- concorrência: 1, 5, 10
- `top_k`: 5 e 10
- `min_score`: 0.75
- mesma região/cloud em todas as rodadas

### 5.2 Métricas obrigatórias
- Latência: p50, p95, p99
- Qualidade: Recall@k, NDCG@10, MRR
- Custo: USD/query (token usage)
- Infra: tempo de indexação, consumo de memória no Qdrant

Relatar por cenário (A/B):
- `embedding_latency_ms` (sem cache vs com cache)
- `vector_search_latency_ms` (sem cache vs com cache)
- `llm_generation_latency_ms` (sem cache vs com cache)
- `pipeline_total_latency_ms` (sem cache vs com cache)
- `cache_hit_rate` por componente no cenário B
- `delta_percentual` por métrica entre A e B

### 5.3 Scripts
Criar:
- `scripts/benchmark/run_indexing.sh`
- `scripts/benchmark/run_queries.sh`
- `scripts/benchmark/evaluate.py`
- `scripts/benchmark/export_results.sh`
- `scripts/benchmark/run_cache_off.sh`
- `scripts/benchmark/run_cache_on.sh`

Saídas:
- `results/baseline/metrics.csv`
- `results/baseline/latency.json`
- `results/baseline/quality.json`
- `results/baseline/cost.json`
- `results/baseline/cache_comparison.csv`
- `results/baseline/cache_comparison.json`

Aceite:
- Um único comando gera resultados finais:
```bash
bash scripts/benchmark/run_all.sh
```

---

## Fase 6 — API/CLI para execução rápida por terceiros

### 6.1 Comando único de setup
Criar `scripts/setup.sh` com:
1. `composer install`
2. copiar `.env.example` para `.env`
3. `php artisan key:generate`
4. `php artisan migrate --seed`
5. validação obrigatória de `OPENAI_API_KEY` preenchida
6. validação de conectividade OpenAI + Qdrant
6. indexação inicial do dataset

### 6.2 Smoke test automatizado
Criar `scripts/smoke_test.sh`:
1. upload de documento
2. consulta semântica
3. resposta RAG
4. assert de status HTTP e campos mínimos

Aceite:
- Usuário novo executa:
```bash
bash scripts/setup.sh
bash scripts/smoke_test.sh
bash scripts/benchmark/run_all.sh
```
sem ajustes de código.

---

## Fase 7 — Interface web mínima em `/` (fluxo completo)

### 7.1 Página raiz para reprodução
Criar interface simplificada em `http://localhost:8000/` com:
1. Upload de documento (`.txt`/`.pdf`)
2. Campo de pergunta/prompt
3. Toggle de execução: `Sem cache` / `Com cache`
3. Botão “Processar com RAG”
4. Exibição de:
   - chunks recuperados (com score)
  - resposta final do `gpt-4o`
   - tempo total (ms)
  - tempo por etapa (embedding, busca vetorial, geração)
  - status de cache hit/miss por etapa

### 7.2 Rotas mínimas
- `GET /` → render da tela
- `POST /upload` → upload + indexação
- `POST /ask` → busca vetorial + geração de resposta

### 7.3 Critérios de aceite da UI
- Usuário consegue reproduzir o fluxo sem Postman:
  1) subir app, 2) enviar documento, 3) perguntar, 4) visualizar resposta RAG.
- Em caso de erro de credencial, tela mostra mensagem explícita sobre `OPENAI_API_KEY`.

---

## Fase 8 — Hard constraints (não negociar)

1. Não introduzir seleção dinâmica de modelos.
2. Não trocar embedding model.
3. Não trocar LLM principal.
4. Não trocar distância vetorial (manter Cosine).
5. Não reintroduzir lógica multi-tenant (`company_id`) no baseline de reprodução.

---

## Definição de pronto (DoD)

- [ ] RAG funcional ponta a ponta (ingestão → indexação → recuperação → resposta)
- [ ] Modelos fixos exatamente iguais ao baseline ProbY
- [ ] Benchmark reproduzível com scripts e dataset versionado
- [ ] Resultados exportáveis em CSV/JSON
- [ ] Documentação de ambiente e protocolo completa

---

## Checklist técnico para o agente executor

1. Implementar serviços base (`QdrantService`, `ChatGPTServiceV2`, `DocumentTextExtractorService`, `DocumentVectorizationService`).
2. Implementar requests/controllers/endpoints.
3. Implementar migrations e seed de benchmark.
4. Implementar scripts de setup/smoke/benchmark.
5. Rodar benchmark e anexar artefatos em `results/baseline/`.
6. Validar repetibilidade com ambiente limpo.

---

## Comandos finais esperados (operador do experimento)

```bash
cp .env.example .env
# preencher OPENAI_API_KEY, QDRANT_URL, QDRANT_API_KEY

bash scripts/setup.sh
bash scripts/smoke_test.sh
bash scripts/benchmark/run_all.sh
```

Resultado esperado:
- API RAG operando
- benchmark executado
- métricas salvas e prontas para anexar ao artigo
