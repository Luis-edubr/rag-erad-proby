# RAG-ERAD — Repositório de Reprodutibilidade Experimental

Este repositório implementa o pipeline experimental descrito no artigo:

**“Análise de Eficiência Computacional em Sistemas RAG com Busca Vetorial: Um Estudo de Caso no ProbY”**  
Luis Oliveira, Douglas Zanini, Alex Camargo  
ProbY: A Smart Platform for Global Problems — Bagé/RS, Brasil

O objetivo deste projeto é permitir que **avaliadores, revisores e qualquer pessoa interessada** consigam reproduzir o experimento de forma simples, com comandos diretos para:

- setup do ambiente,
- ingestão e indexação de documentos,
- smoke test ponta a ponta,
- benchmark com cenários **sem cache** e **com cache**.

---

## 1) Escopo técnico reproduzido

Este baseline mantém as mesmas decisões centrais do experimento:

- **LLM de geração:** `gpt-4o`
- **Modelo de embeddings:** `text-embedding-ada-002`
- **Vector DB:** Qdrant Cloud
- **Coleção:** `rag_experiment`
- **Dimensão vetorial:** `1536`
- **Distância vetorial:** `Cosine`
- **Fallback OCR para PDF:** via OpenAI (`gpt-4o`) quando parser local falha

---

## 2) Arquitetura do pipeline

Pipeline executado no projeto:

1. Upload/ingestão de documentos (`.txt` / `.pdf`)
2. Extração de texto
3. Chunking com sobreposição
4. Geração de embeddings
5. Indexação no Qdrant
6. Busca semântica `top_k`
7. Geração da resposta final com contexto recuperado

Também há execução comparativa de desempenho:

- **Cenário A:** cache desabilitado
- **Cenário B:** cache habilitado

---

## 3) Pré-requisitos (máquina local)

Instale os itens abaixo antes de executar os scripts:

- **Linux/macOS/WSL** (recomendado)
- **PHP 8.3+**
- **Composer 2+**
- **Node.js 20+** e **npm 10+**
- **Extensões PHP comuns para Laravel** (ex.: `mbstring`, `xml`, `curl`, `zip`, `pdo`)
- **Banco relacional local** (MySQL/MariaDB ou equivalente), conforme configuração do `.env`
- **Conta/API Key OpenAI**
- **Instância Qdrant Cloud** + URL + API Key

### Checagem rápida de versões

```bash
php -v
composer -V
node -v
npm -v
```

---

## 4) Configuração de credenciais e ambiente

> Importante: não versionar `.env` com credenciais reais.

1. Crie seu arquivo de ambiente:

```bash
cp .env.example .env
```

2. Edite `.env` e preencha ao menos:

```dotenv
OPENAI_API_KEY=...
QDRANT_URL=...
QDRANT_API_KEY=...
QDRANT_PORT=443
QDRANT_USE_TLS=true
QDRANT_VERIFY_SSL=true
```

3. Configure também banco de dados local no `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

---

## 5) Instalação local (passo a passo)

### Opção recomendada (fluxo do experimento)

```bash
bash scripts/setup.sh
```

O `setup.sh` executa:

1. `composer install`
2. Criação de `.env` (se não existir)
3. `php artisan key:generate`
4. `php artisan migrate --seed`
5. Validação de `OPENAI_API_KEY`
6. Validação de conectividade OpenAI + Qdrant
7. Indexação inicial do dataset em `datasets/rag-benchmark/documents`

### Front-end (interface web)

Para usar a interface em `/` com assets atualizados:

```bash
npm install
npm run build
```

(Em desenvolvimento, você pode usar `npm run dev`.)

---

## 6) Como executar o experimento completo

Após configurar o `.env` e rodar setup:

```bash
bash scripts/smoke_test.sh
bash scripts/benchmark/run_all.sh
```

Esse é o fluxo esperado de avaliação:

```bash
bash scripts/setup.sh
bash scripts/smoke_test.sh
bash scripts/benchmark/run_all.sh
```

Sem necessidade de ajustes de código.

---

## 7) Saídas esperadas

Os artefatos do benchmark são gerados em `results/baseline/`, incluindo:

- `metrics.csv`
- `latency.json`
- `cache_comparison.csv`
- `cache_comparison.json`

Esses arquivos permitem comparar os cenários com e sem cache e analisar latência do pipeline.

---

## 8) Execução manual da aplicação (opcional)

Para usar a API/UI manualmente:

```bash
php artisan serve
```

Acesse:

- Interface web: `http://localhost:8000/`
- Endpoints API:
  - `POST /api/rag/search`
  - `POST /api/rag/answer`

Rotas web implementadas:

- `GET /` (tela principal)
- `POST /upload` (upload + indexação)
- `POST /ask` (busca vetorial + resposta RAG)

---

## 9) Mapeamento com o artigo

Este repositório foi estruturado para dar suporte direto aos pontos experimentais discutidos no artigo, especialmente:

- avaliação por etapa (embedding, busca vetorial, geração),
- comparação de latência com/sem cache,
- reprodutibilidade do fluxo em ambiente controlado,
- observação de que a etapa de geração tende a concentrar a maior parcela da latência total.

---

## 10) Solução de problemas comuns

### `OPENAI_API_KEY is not configured`
Preencha a chave no `.env` e rode novamente:

```bash
php artisan config:clear
bash scripts/setup.sh
```

### Erro de conexão com Qdrant
Verifique `QDRANT_URL`, `QDRANT_API_KEY`, `QDRANT_PORT`, `QDRANT_USE_TLS` no `.env` e teste:

```bash
php artisan qdrant:ensure-collection
```

### Coleção não encontrada
Crie automaticamente:

```bash
php artisan qdrant:ensure-collection
```

---

## 11) Contato e contexto

Projeto relacionado ao estudo de caso da plataforma ProbY.  
Site institucional: https://proby.online

---

## 12) Referências do artigo

- Gupta et al. (2024) — Survey de RAG
- Ji et al. (2023) — Hallucination em geração de linguagem
- Johnson et al. (2025) — Busca vetorial eficiente (Faiss)
- Zhao et al. (2026) — RAG para conteúdo gerado por IA

(Detalhamento completo das referências no manuscrito do artigo.)
