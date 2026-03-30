<?php

namespace App\Providers;

use App\Services\CacheService;
use App\Services\ChatGPTServiceV2;
use App\Services\DocumentTextExtractorService;
use App\Services\DocumentVectorizationService;
use App\Services\QdrantService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheService::class, function () {
            return new CacheService();
        });

        $this->app->singleton(QdrantService::class, function () {
            return new QdrantService();
        });

        $this->app->singleton(ChatGPTServiceV2::class, function ($app) {
            return new ChatGPTServiceV2($app->make(CacheService::class));
        });

        $this->app->singleton(DocumentTextExtractorService::class, function ($app) {
            return new DocumentTextExtractorService($app->make(ChatGPTServiceV2::class));
        });

        $this->app->singleton(DocumentVectorizationService::class, function ($app) {
            return new DocumentVectorizationService(
                $app->make(DocumentTextExtractorService::class),
                $app->make(ChatGPTServiceV2::class),
                $app->make(QdrantService::class),
                $app->make(CacheService::class),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
