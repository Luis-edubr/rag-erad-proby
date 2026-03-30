<?php

$rawUrl = trim((string) env('QDRANT_URL', 'localhost'));
$defaultScheme = env('QDRANT_USE_TLS', true) ? 'https' : 'http';
$defaultPort = (int) env('QDRANT_PORT', 6333);

if ($rawUrl === '' || $rawUrl === 'http' || $rawUrl === 'https') {
    $host = 'localhost';
    $scheme = $defaultScheme;
    $port = $defaultPort;
} elseif (str_starts_with($rawUrl, 'http://') || str_starts_with($rawUrl, 'https://')) {
    $parts = parse_url($rawUrl);
    $scheme = $parts['scheme'] ?? $defaultScheme;
    $host = $parts['host'] ?? 'localhost';
    $port = $parts['port'] ?? $defaultPort;
} else {
    $scheme = $defaultScheme;
    $host = preg_replace('#^https?://#', '', $rawUrl) ?: 'localhost';
    $port = $defaultPort;
}

$url = "{$scheme}://{$host}:{$port}";

return [
    'url' => $url,
    'api_key' => env('QDRANT_API_KEY', ''),
    'collection_name' => 'rag_experiment',
    'vector_dimension' => 1536,
    'distance_metric' => 'Cosine',
    'timeout' => 60,
    'connect_timeout' => 10,
    'indexing_threshold' => 10000,
    'verify_ssl' => env('QDRANT_VERIFY_SSL', true),
];
