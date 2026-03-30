# Métricas do pipeline RAG (projeto rag-erad-proby)

Valores **medidos** por `php artisan rag:benchmark-pipeline --both` (latências reais por etapa). Custos são **estimativas** por contagem aproximada de tokens (chars/4), exceto Qdrant (custo infra próprio ≈ **0 USD** na API OpenAI).

Preços referência: embedding 0.00010 USD/1k tokens; gpt-4o 2.50/1M in + 10.00/1M out (config/rag.php).

### Por que este desenho tende a ser mais eficiente
- **Recuperação vetorial** restringe o contexto enviado ao LLM a trechos relevantes, em geral **menor** que enviar toda a base ou prompts enormes sem RAG.
- **Embeddings (ada-002)** custam muito menos por token que **geração (gpt-4o)**; separar etapas permite cachear embeddings e resultados de busca para consultas repetidas.
- **Baseline de referência** na última linha é uma ordem de grandeza para “só LLM”; substitua por medições suas se precisar de rigor comparativo.

### Tabela comparativa (P50)

| Cenário | Embedding (ms) | Busca vetorial (ms) | LLM (ms) | Total (ms) | $ embedding | $ busca* | $ LLM | $ total |
|---------|----------------|---------------------|----------|------------|-------------|---------|-------|---------|
| Cache **OFF** (medido) | 308.52 | 121.20 | 2966.13 | 3385.51 | 0.000001 | 0.000000 | 0.007672 | 0.007673 |
| Cache **ON** (medido) | 1.30 | 0.00 | 0.39 | 1.56 | 0.000001 | 0.000000 | 0.007784 | 0.007785 |
| Baseline típico: só LLM (1× gpt-4o, prompt longo sem recuperação) | — | — | — | **4000** | — | — | — | **0.0200** |

\* Busca em Qdrant auto-hospedado: custo API OpenAI **0**; infraestrutura própria não entra nesta coluna.

### Observação sobre cache
Com **consultas todas distintas** no benchmark, o ganho de cache pode ser pequeno (cache por texto de query). Repetindo a mesma pergunta, embedding + resultados de busca + resposta podem ser reutilizados — aí o modo **cache ON** mostra ganho maior de latência e custo.

_Gerado automaticamente. Regenerar: `php artisan rag:benchmark-pipeline --both`_
