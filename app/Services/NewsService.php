<?php

namespace App\Services;

use App\Repositories\EloquentArticleRepository;
use App\Integrations\News\ProviderAggregator;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * News Service
 * 
 * Manages news article retrieval from providers and database.
 * Handles both headlines and search functionality with freshness checking.
 * 
 * @package App\Services
 */
class NewsService
{
    public const MODE_HEADLINES = 'headlines';
    public const MODE_SEARCH    = 'search';

    public function __construct(
        private EloquentArticleRepository $articleRepository,
        private ProviderAggregator $providerAggregator,
    ) {}

    /**
     * Get top headlines
     * 
     * @param array $params Filter parameters
     * @param int $page Page number
     * @param int $pageSize Results per page
     * @return LengthAwarePaginator
     */
    public function getHeadlines(array $params, int $page, int $pageSize): LengthAwarePaginator
    {
        return $this->fetchArticles($params, $page, $pageSize, self::MODE_HEADLINES);
    }

    /**
     * Search articles
     * 
     * @param array $params Search parameters (must include keyword)
     * @param int $page Page number
     * @param int $pageSize Results per page
     * @return LengthAwarePaginator
     */
    public function searchArticles(array $params, int $page, int $pageSize): LengthAwarePaginator
    {
        return $this->fetchArticles($params, $page, $pageSize, self::MODE_SEARCH);
    }

    /**
     * Core article fetching logic with intelligent caching
     * 
     * @param array $params Filter/search parameters
     * @param int $page Page number
     * @param int $pageSize Results per page
     * @param string $mode MODE_HEADLINES or MODE_SEARCH
     * @return LengthAwarePaginator
     */
    private function fetchArticles(array $params, int $page, int $pageSize, string $mode): LengthAwarePaginator
    {
        // Always try database first
        $paginator = $this->articleRepository->search($params, $page, $pageSize);

        // If we have results on first page, return them (avoid unnecessary API calls)
        if ($page === 1 && $paginator->total() > 0) {
            Log::info("[NewsService] Serving from database: {$paginator->total()} articles");
            return $paginator;
        }

        // Create cache key for this specific query
        $cacheKey = $this->getCacheKey($params, $mode);
        $lastFetchTime = Cache::get($cacheKey);
        $freshMinutes = (int) config('news.freshness.' . ($mode === self::MODE_SEARCH ? 'search_minutes' : 'headlines_minutes'), 15);
        
        // Check if we've fetched recently to avoid duplicate API calls
        $shouldFetch = !$lastFetchTime || 
                      Carbon::parse($lastFetchTime)->lt(Carbon::now()->subMinutes($freshMinutes));

        if ($shouldFetch && ($paginator->total() === 0 || $page === 1)) {
            Log::info("[NewsService] Fetching fresh content from providers", [
                'mode' => $mode,
                'cache_key' => $cacheKey,
                'last_fetch' => $lastFetchTime
            ]);

            $this->fetchFromProviders($params, $page, $pageSize, $mode, $cacheKey, $freshMinutes);
            
            // Get updated results after fetching
            $paginator = $this->articleRepository->search($params, $page, $pageSize);
        }

        return $paginator;
    }

    /**
     * Fetch articles from external providers
     * 
     * @param array $params
     * @param int $page
     * @param int $pageSize
     * @param string $mode
     * @param string $cacheKey
     * @param int $freshMinutes
     * @return void
     */
    private function fetchFromProviders(array $params, int $page, int $pageSize, string $mode, string $cacheKey, int $freshMinutes): void
    {
        $dtos = collect();
        $errors = [];

        foreach ($this->providerAggregator->enabled() as $provider) {
            try {
                $chunk = $mode === self::MODE_SEARCH
                    ? $provider->everything($params)
                    : $provider->topHeadlines($params);
                
                $dtos = $dtos->merge($chunk);
                Log::info("[NewsService] Fetched {$chunk->count()} articles from {$provider::key()}");
                
            } catch (\Exception $e) {
                $errors[] = "Provider {$provider::key()}: {$e->getMessage()}";
                Log::error("[NewsService] Provider fetch failed", [
                    'provider' => $provider::key(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        if ($dtos->isNotEmpty()) {
            $this->articleRepository->upsertFromDTOs($dtos->all());
            Log::info("[NewsService] Upserted {$dtos->count()} articles");
        }

        // Cache the fetch time to prevent duplicate calls
        Cache::put($cacheKey, Carbon::now()->toISOString(), now()->addMinutes($freshMinutes));

        if (!empty($errors)) {
            Log::warning("[NewsService] Some providers failed", ['errors' => $errors]);
        }
    }

    /**
     * Generate cache key for fetch tracking
     * 
     * @param array $params
     * @param string $mode
     * @return string
     */
    private function getCacheKey(array $params, string $mode): string
    {
        // Create a stable cache key from relevant parameters
        $keyData = [
            'mode' => $mode,
            'keyword' => $params['keyword'] ?? null,
            'category' => $params['category'] ?? null,
            'publisher' => $params['publisher'] ?? null,
            'provider' => $params['provider'] ?? null,
            'author' => $params['author'] ?? null,
            'from' => $params['from'] ?? null,
            'to' => $params['to'] ?? null,
        ];

        return 'news_fetch:' . md5(serialize($keyData));
    }
}
