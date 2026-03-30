<?php

namespace App\DataTransferObjects;

class DocumentEmbeddingData
{
    public function __construct(
        public readonly string $filename,
        public readonly int $chunkIndex,
        public readonly string $chunkText,
        public readonly array $vector,
        public readonly \DateTime $uploadDate,
        public readonly ?array $metadata = null,
    ) {
    }

    public function toQdrantPoint(string $id): array
    {
        return [
            'id' => $id,
            'vector' => $this->vector,
            'payload' => [
                'filename' => $this->filename,
                'chunk_index' => $this->chunkIndex,
                'chunk_text' => $this->chunkText,
                'upload_date' => $this->uploadDate->toIso8601String(),
                'metadata' => $this->metadata ?? [],
            ],
        ];
    }
}
