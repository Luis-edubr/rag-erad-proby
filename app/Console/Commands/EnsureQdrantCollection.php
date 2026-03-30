<?php

namespace App\Console\Commands;

use App\Services\QdrantService;
use Illuminate\Console\Command;

class EnsureQdrantCollection extends Command
{
    protected $signature = 'qdrant:ensure-collection';
    protected $description = 'Ensure Qdrant collection exists, create if necessary';

    public function __construct(private QdrantService $qdrantService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Checking Qdrant connection...');

        if ($this->qdrantService->ensureCollectionExists()) {
            $this->info('✓ Collection already exists');
            return self::SUCCESS;
        }

        $this->info('Collection does not exist. Creating...');

        try {
            $this->qdrantService->createCollection();
            $this->info('✓ Collection created successfully');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('✗ Failed to create collection: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
