<?php

namespace App\Jobs;

use App\Repositories\EloquentArticleRepository;
use App\Integrations\News\ProviderAggregator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class FetchNewsArticles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance
     * 
     * @param array $params Request parameters for fetching articles
     * @param string $mode Fetch mode (headlines or search)
     * @param string $cacheKey Cache key for tracking fetch times
     * @param int $freshMinutes Minutes after which data becomes stale
     */
    public function __construct(
        private array $params,
        private string $mode,
        private string $cacheKey,
        private int $freshMinutes
    ) {}

    /**
     * Execute the job
     * 
     * @param EloquentArticleRepository $articleRepository Repository for storing articles
     * @param ProviderAggregator $providerAggregator Aggregator for news providers
     * @return void
     */
    public function handle(
        EloquentArticleRepository $articleRepository,
        ProviderAggregator $providerAggregator
    ): void {
        Log::info("[FetchNewsArticles] Starting background fetch", [
            'mode' => $this->mode,
            'cache_key' => $this->cacheKey
        ]);

        $dtos = collect();
        $errors = [];

        foreach ($providerAggregator->enabled() as $provider) {
            try {
                $chunk = $this->mode === 'search'
                    ? $provider->searchArticles($this->params)
                    : $provider->topHeadlines($this->params);
                
                $dtos = $dtos->merge($chunk);
                Log::info("[FetchNewsArticles] Fetched {$chunk->count()} articles from {$provider::key()}");
                
            } catch (\Exception $e) {
                $errors[] = "Provider {$provider::key()}: {$e->getMessage()}";
                Log::error("[FetchNewsArticles] Provider fetch failed", [
                    'provider' => $provider::key(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        if ($dtos->isNotEmpty()) {
            $articleRepository->upsertFromDTOs($dtos->all());
            Log::info("[FetchNewsArticles] Upserted {$dtos->count()} articles");
        }

        // Cache the fetch time to prevent duplicate calls
        Cache::put($this->cacheKey, Carbon::now()->toISOString(), now()->addMinutes($this->freshMinutes));

        if (!empty($errors)) {
            Log::warning("[FetchNewsArticles] Some providers failed", ['errors' => $errors]);
        }

        Log::info("[FetchNewsArticles] Background fetch completed", [
            'total_articles' => $dtos->count(),
            'errors_count' => count($errors)
        ]);
    }

    /**
     * Handle a job failure
     * 
     * @param \Throwable $exception The exception that caused the failure
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("[FetchNewsArticles] Job failed", [
            'mode' => $this->mode,
            'cache_key' => $this->cacheKey,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}