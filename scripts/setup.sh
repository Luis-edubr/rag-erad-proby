#!/bin/bash

set -e

# Setup script for RAG application
# Installs dependencies, configures environment, validates connectivity, and indexes dataset

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

echo "==================================================================="
echo "  RAG Setup — Initializing project"
echo "==================================================================="

# Step 1: Install composer dependencies
echo ""
echo "[1/7] Installing composer dependencies..."
composer install --no-interaction
if [ $? -eq 0 ]; then
    echo "✓ Composer dependencies installed"
else
    echo "✗ Failed to install composer dependencies"
    exit 1
fi

# Step 2: Copy .env.example to .env (if not exists)
echo ""
echo "[2/7] Configuring .env file..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✓ Created .env from .env.example"
else
    echo "✓ .env file already exists"
fi

# Step 3: Generate application key
echo ""
echo "[3/7] Generating application key..."
php artisan key:generate --force
if [ $? -eq 0 ]; then
    echo "✓ Application key generated"
else
    echo "✗ Failed to generate application key"
    exit 1
fi

# Step 4: Run migrations and seeders
echo ""
echo "[4/7] Running migrations and seeders..."
php artisan migrate --seed --force
if [ $? -eq 0 ]; then
    echo "✓ Migrations and seeds completed"
else
    echo "✗ Failed to run migrations/seeders"
    exit 1
fi

# Step 5: Validate OPENAI_API_KEY
echo ""
echo "[5/7] Validating OPENAI_API_KEY..."
OPENAI_KEY=$(grep "^OPENAI_API_KEY=" .env | cut -d'=' -f2)
if [ -z "$OPENAI_KEY" ] || [ "$OPENAI_KEY" = '""' ] || [ "$OPENAI_KEY" = "''" ]; then
    echo "✗ OPENAI_API_KEY is not set in .env"
    echo ""
    echo "Please configure OPENAI_API_KEY in .env file:"
    echo "  1. Edit .env"
    echo "  2. Set OPENAI_API_KEY=sk-... (your OpenAI API key)"
    echo "  3. Run setup again: bash scripts/setup.sh"
    exit 1
else
    echo "✓ OPENAI_API_KEY is configured"
fi

# Step 6: Validate OpenAI connectivity
echo ""
echo "[6/7] Validating OpenAI API connectivity..."
php << 'PHPEOF'
<?php
require 'vendor/autoload.php';
$env_file = implode("\n", file('.env'));
preg_match('/^OPENAI_API_KEY=(.*)$/m', $env_file, $matches);
$api_key = trim($matches[1] ?? '', '\'"');

if (empty($api_key)) {
    echo "✗ OPENAI_API_KEY not found\n";
    exit(1);
}

$client = new \GuzzleHttp\Client();
try {
    $response = $client->post('https://api.openai.com/v1/embeddings', [
        'headers' => [
            'Authorization' => "Bearer {$api_key}",
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'model' => 'text-embedding-ada-002',
            'input' => 'test connectivity',
        ],
        'timeout' => 10,
    ]);
    if ($response->getStatusCode() === 200) {
        echo "✓ OpenAI API connectivity validated\n";
        exit(0);
    } else {
        echo "✗ OpenAI API returned status " . $response->getStatusCode() . "\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "✗ OpenAI API connectivity failed: " . $e->getMessage() . "\n";
    exit(1);
}
PHPEOF

if [ $? -ne 0 ]; then
    echo "✗ OpenAI API validation failed"
    exit 1
fi

# Step 7: Validate Qdrant connectivity and index dataset
echo ""
echo "[7/7] Validating Qdrant connectivity and indexing dataset..."
php artisan tinker --execute='
try {
    // Ensure Qdrant collection exists
    $qdrant = app(\App\Services\QdrantService::class);
    if (!$qdrant->ensureCollectionExists()) {
        throw new Exception("Failed to ensure Qdrant collection");
    }
    echo "✓ Qdrant collection verified\n";

    // Index initial dataset
    $vectorizer = app(\App\Services\DocumentVectorizationService::class);
    $documentsPath = base_path("datasets/rag-benchmark/documents");
    
    if (!is_dir($documentsPath)) {
        echo "⚠ Dataset directory not found at {$documentsPath}\n";
        echo "Dataset will be indexed when first document is uploaded\n";
    } else {
        $files = array_diff(scandir($documentsPath), ["..", "."]);
        if (empty($files)) {
            echo "⚠ No documents found in dataset directory\n";
        } else {
            foreach ($files as $file) {
                $filePath = "{$documentsPath}/{$file}";
                if (is_file($filePath) && in_array(pathinfo($filePath, PATHINFO_EXTENSION), ["txt", "pdf"])) {
                    $uploadedFile = new \Illuminate\Http\UploadedFile(
                        $filePath,
                        $file,
                        mime_content_type($filePath),
                        null,
                        true
                    );
                    $pointsCount = $vectorizer->vectorizeAndIndex($uploadedFile);
                    echo "✓ Indexed {$file} ({$pointsCount} vectors)\n";
                }
            }
        }
    }
} catch (\Exception $e) {
    echo "✗ Qdrant validation failed: " . $e->getMessage() . "\n";
    exit(1);
}
'

if [ $? -eq 0 ]; then
    echo "✓ Qdrant connectivity and dataset indexing successful"
else
    echo "✗ Qdrant validation failed"
    exit 1
fi

echo ""
echo "==================================================================="
echo "  ✓ Setup complete! RAG application is ready."
echo "==================================================================="
echo ""
echo "Next steps:"
echo "  1. Verify your configuration: grep '^QDRANT' .env"
echo "  2. Run smoke tests: bash scripts/smoke_test.sh"
echo "  3. Start benchmark: bash scripts/benchmark/run_all.sh"
echo ""
