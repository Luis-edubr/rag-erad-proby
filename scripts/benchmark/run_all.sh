#!/bin/bash

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"
RESULTS_DIR="$PROJECT_ROOT/results/baseline"

mkdir -p "$RESULTS_DIR"

echo "=========================================="
echo "RAG Benchmark Suite"
echo "=========================================="
echo ""

echo "[1/5] Running indexing..."
bash "$SCRIPT_DIR/run_indexing.sh"

echo ""
echo "[2/5] Running benchmark queries (no cache)..."
bash "$SCRIPT_DIR/run_cache_off.sh"

echo ""
echo "[3/5] Running benchmark queries (with cache)..."
bash "$SCRIPT_DIR/run_cache_on.sh"

echo ""
echo "[4/5] Evaluating and exporting results..."
bash "$SCRIPT_DIR/export_results.sh"

echo ""
echo "[5/5] Pipeline por etapa (latência + custo) + tabela Markdown..."
bash "$SCRIPT_DIR/run_pipeline_metrics.sh"

echo ""
echo "=========================================="
echo "Benchmark completed successfully!"
echo "Results saved to: $RESULTS_DIR"
echo "=========================================="
