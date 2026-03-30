#!/bin/bash

set -e

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$PROJECT_ROOT"

echo "Ensuring Qdrant collection exists..."
php artisan qdrant:ensure-collection

echo "Indexing documents from datasets..."
php artisan tinker <<'EOF'
use App\Models\Document;
use App\Services\DocumentVectorizationService;
use Symfony\Component\HttpFoundation\File\File;

$vectorizationService = app(DocumentVectorizationService::class);
$documentsPath = base_path('datasets/rag-benchmark/documents');

if (is_dir($documentsPath)) {
    $files = array_diff(scandir($documentsPath), ['.', '..']);
    
    foreach ($files as $file) {
        $filePath = "$documentsPath/$file";
        
        if (!is_file($filePath)) {
            continue;
        }
        
        echo "Indexing: $file\n";
        
        $uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(
            $filePath,
            $file,
            mime_content_type($filePath) ?: 'text/plain',
            null,
            true
        );
        
        $pointsCount = $vectorizationService->vectorizeAndIndex($uploadedFile);
        echo "  ✓ Indexed $pointsCount points\n";
    }
}

echo "Indexing complete!\n";
EOF

echo "✓ Indexing completed"
