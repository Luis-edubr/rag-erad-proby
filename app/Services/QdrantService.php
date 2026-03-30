<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class QdrantService
{
    private Client $client;

    private string $baseUrl;

    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('qdrant.url'), '/');
        $this->apiKey = config('qdrant.api_key');

        if (! filter_var($this->baseUrl, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Invalid Qdrant URL. Check QDRANT_URL, QDRANT_PORT and QDRANT_USE_TLS in environment configuration.');
        }

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => config('qdrant.timeout'),
            'connect_timeout' => config('qdrant.connect_timeout'),
            'verify' => config('qdrant.verify_ssl', true),
            'headers' => [
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function ensureCollectionExists(): bool
    {
        try {
            $response = $this->client->get($this->getCollectionsEndpoint());

            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            Log::warning('Collection check failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Número de pontos/vetores na coleção (para métricas de volume na UI).
     */
    public function getCollectionPointsCount(): ?int
    {
        try {
            $response = $this->client->get($this->getCollectionsEndpoint());
            $data = json_decode($response->getBody()->getContents(), true);
            $n = $data['result']['points_count'] ?? null;

            return $n !== null ? (int) $n : null;
        } catch (GuzzleException $e) {
            Log::debug('Qdrant collection points_count unavailable', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function createCollection(): void
    {
        try {
            $payload = [
                'vectors' => [
                    'size' => config('qdrant.vector_dimension'),
                    'distance' => config('qdrant.distance_metric'),
                ],
                'optimizers_config' => [
                    'indexing_threshold' => config('qdrant.indexing_threshold'),
                ],
            ];

            $this->client->put(
                $this->getCollectionsEndpoint(),
                ['json' => $payload]
            );

            Log::info('Qdrant collection created', ['collection' => config('qdrant.collection_name')]);
        } catch (GuzzleException $e) {
            Log::error('Failed to create collection', [
                'error' => $e->getMessage(),
                'collection' => config('qdrant.collection_name'),
            ]);
            throw $e;
        }
    }

    public function upsertPoints(array $points): void
    {
        try {
            $payload = ['points' => $points];

            $this->client->put(
                $this->getPointsEndpoint(),
                ['json' => $payload]
            );

            Log::info('Points upserted', ['count' => count($points)]);
        } catch (GuzzleException $e) {
            Log::error('Failed to upsert points', [
                'count' => count($points),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function searchByVector(array $vector, int $limit = 10): array
    {
        try {
            $payload = [
                'vector' => $vector,
                'limit' => $limit,
                'with_payload' => true,
            ];

            $response = $this->client->post(
                $this->getSearchEndpoint(),
                ['json' => $payload]
            );

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['result'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('Search failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function scrollPoints(int $limit = 100, ?string $offset = null): array
    {
        try {
            $payload = [
                'limit' => $limit,
                'with_payload' => true,
            ];

            if ($offset) {
                $payload['offset'] = $offset;
            }

            $response = $this->client->post(
                $this->getScrollEndpoint(),
                ['json' => $payload]
            );

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['result'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('Scroll failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function deletePoints(array $pointIds): void
    {
        try {
            $payload = ['points' => $pointIds];

            $this->client->post(
                $this->getDeletePointsEndpoint(),
                ['json' => $payload]
            );

            Log::info('Points deleted', ['count' => count($pointIds)]);
        } catch (GuzzleException $e) {
            Log::error('Failed to delete points', [
                'count' => count($pointIds),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function deletePointsByFilter(array $filter): void
    {
        try {
            $payload = ['filter' => $filter];

            $this->client->post(
                $this->getDeleteByFilterEndpoint(),
                ['json' => $payload]
            );

            Log::info('Points deleted by filter');
        } catch (GuzzleException $e) {
            Log::error('Failed to delete points by filter', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function getCollectionsEndpoint(): string
    {
        return '/collections/'.config('qdrant.collection_name');
    }

    private function getPointsEndpoint(): string
    {
        return '/collections/'.config('qdrant.collection_name').'/points';
    }

    private function getSearchEndpoint(): string
    {
        return '/collections/'.config('qdrant.collection_name').'/points/search';
    }

    private function getScrollEndpoint(): string
    {
        return '/collections/'.config('qdrant.collection_name').'/points/scroll';
    }

    private function getDeletePointsEndpoint(): string
    {
        return '/collections/'.config('qdrant.collection_name').'/points/delete';
    }

    private function getDeleteByFilterEndpoint(): string
    {
        return '/collections/'.config('qdrant.collection_name').'/points/delete';
    }
}
