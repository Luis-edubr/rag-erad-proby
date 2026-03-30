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

echo "[1/4] Running indexing..."
bash "$SCRIPT_DIR/run_indexing.sh"

echo ""
echo "[2/4] Running benchmark queries (no cache)..."
bash "$SCRIPT_DIR/run_cache_off.sh"

echo ""
echo "[3/4] Running benchmark queries (with cache)..."
bash "$SCRIPT_DIR/run_cache_on.sh"

echo ""
echo "[4/4] Evaluating and exporting results..."
bash "$SCRIPT_DIR/export_results.sh"

echo ""
echo "=========================================="
echo "Benchmark completed successfully!"
echo "Results saved to: $RESULTS_DIR"
echo "=========================================="
