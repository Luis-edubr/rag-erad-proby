#!/bin/bash

set -e

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$PROJECT_ROOT"

echo "Medindo pipeline completo (embedding + Qdrant + LLM) e gerando METRICAS_PIPELINE.md..."
php artisan rag:benchmark-pipeline --both
