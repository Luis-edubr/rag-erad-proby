<?php

namespace Database\Seeders;

use App\Models\Document;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $documentsPath = base_path('datasets/rag-benchmark/documents');

        if (!is_dir($documentsPath)) {
            return;
        }

        $files = array_diff(scandir($documentsPath), ['.', '..']);

        foreach ($files as $file) {
            if (!is_file("$documentsPath/$file")) {
                continue;
            }

            $filePath = "$documentsPath/$file";
            $fileSize = filesize($filePath);
            $mimeType = mime_content_type($filePath) ?: 'text/plain';

            $storagePath = Storage::disk('local')->putFileAs(
                'documents',
                new \Symfony\Component\HttpFoundation\File\File($filePath),
                $file
            );

            Document::create([
                'file_name' => $storagePath,
                'original_name' => $file,
                'file_type' => $mimeType,
                'file_size' => $fileSize,
                'file_path' => $filePath,
            ]);
        }
    }
}
