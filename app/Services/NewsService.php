<?php

namespace App\Services;

use App\Models\Article;
use App\Repositories\EloquentArticleRepository;
use App\Integrations\News\ProviderAggregator;
use App\Jobs\FetchNewsArticles;
use App\Services\PreferenceService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * News Service
 *
 * Manages news article retrieval from providers and database.
 * Handles both headlines and search functionality with freshness checking.
 * Integrates user preferences for automatic filtering.
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
        private PreferenceService $preferenceService,
    ) {
    }

    /**
     * Find article by ID
     *
     * @param int $id Article ID
     * @return Article|null
     */
    public function findById(int $id): ?Article
    {
        try {
            $article = $this->articleRepository->findById($id);

            if (!$article) {
                Log::warning('[NewsService] Article not found', ['id' => $id]);
                throw new HttpException(404, "Article with ID {$id} does not exist");
            }

            return $article;
        } catch (HttpException $e) {
            // Re-throw HTTP exceptions to preserve status codes (404, etc.)
            throw $e;
        } catch (\Exception $e) {
            Log::error('[NewsService] Failed to retrieve article', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Failed to retrieve article', 500);
        }
    }

    /**
     * Delete article by ID
     *
     * @param int $id Article ID
     * @return bool True if deleted, false if not found
     */
    public function deleteById(int $id): bool
    {
        try {
            $deleted = $this->articleRepository->deleteById($id);

            if (!$deleted) {
                Log::warning('[NewsService] Article not found for deletion', ['id' => $id]);
                throw new HttpException(404, "Article with ID {$id} does not exist or could not be deleted");
            }

            return true;
        } catch (HttpException $e) {
            // Re-throw HTTP exceptions to preserve status codes (404, etc.)
            throw $e;
        } catch (\Exception $e) {
            Log::error('[NewsService] Failed to delete article', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Failed to delete article', 500);
        }
    }

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
        $params = $this->mergeWithPreferences($params);
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
        $params = $this->mergeWithPreferences($params);
        return $this->fetchArticles($params, $page, $pageSize, self::MODE_SEARCH);
    }

    /**
     * Merge user preferences with request parameters
     * Request parameters take precedence over preferences
     *
     * @param array $params Request parameters
     * @return array Merged parameters with preferences applied
     */
    private function mergeWithPreferences(array $params): array
    {
        try {
            // Only apply preferences if we have preferences AND request only contains pagination params
            $hasOnlyPaginationParams = empty(array_diff(array_keys($params), ['page', 'pageSize']));

            if (!$this->preferenceService->hasAnyPreferences() || !$hasOnlyPaginationParams) {
                return $params;
            }

            $preferences = $this->preferenceService->getPreference();

            // Only apply preferences if the specific filter is not already set in params
            if (empty($params['source']) && !empty($preferences->source)) {
                $params['source'] = $preferences->source;
            }

            if (empty($params['category']) && !empty($preferences->category)) {
                $params['category'] = $preferences->category;
            }

            if (empty($params['author']) && !empty($preferences->author)) {
                $params['author'] = $preferences->author;
            }

            Log::info('[NewsService] Applied user preferences to search', [
                'original_params' => array_keys($params),
                'preferences_applied' => array_keys(array_filter($preferences))
            ]);

        } catch (\Exception $e) {
            Log::warning('[NewsService] Failed to apply preferences, proceeding without them', [
                'error' => $e->getMessage()
            ]);
        }

        return $params;
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

        // Create cache key for this specific query
        $cacheKey = $this->getCacheKey($params, $mode);
        $lastFetchTime = Cache::get($cacheKey);
        $freshMinutes = (int) config('news.freshness.' . ($mode === self::MODE_SEARCH ? 'search_minutes' : 'headlines_minutes'), 15);

        // Check if we've fetched recently to avoid duplicate API calls
        $shouldFetch = !$lastFetchTime ||
                      Carbon::parse($lastFetchTime)->lt(Carbon::now()->subMinutes($freshMinutes));

        if ($shouldFetch) {
            $params = array_merge($params, ['page' => $page, 'pageSize' => $pageSize]);

            if ($paginator->total() === 0) {
                // No existing data - wait for fresh fetch synchronously
                Log::info("[NewsService] No existing data, fetching synchronously from providers", [
                    'mode' => $mode,
                    'cache_key' => $cacheKey,
                    'last_fetch' => $lastFetchTime
                ]);

                $this->fetchFromProviders($params, $mode, $cacheKey, $freshMinutes);

                // Get updated results after fetching
                $paginator = $this->articleRepository->search($params, $page, $pageSize);
            } else {
                // Existing data available - fetch in background via queue
                Log::info("[NewsService] Existing data available, dispatching background fetch", [
                    'mode' => $mode,
                    'cache_key' => $cacheKey,
                    'existing_total' => $paginator->total(),
                    'last_fetch' => $lastFetchTime
                ]);

                FetchNewsArticles::dispatch($params, $mode, $cacheKey, $freshMinutes)->onQueue('news');
            }
        } else {
            Log::info("[NewsService] Serving from database: {$paginator->total()} articles", [
                'fresh_until' => $lastFetchTime ? Carbon::parse($lastFetchTime)->addMinutes($freshMinutes)->toISOString() : 'never_fetched'
            ]);
        }

        return $paginator;
    }

    /**
     * Fetch articles from external providers
     *
     * @param array $params
     * @param string $mode
     * @param string $cacheKey
     * @param int $freshMinutes
     * @return void
     */
    private function fetchFromProviders(array $params, string $mode, string $cacheKey, int $freshMinutes): void
    {
        $dtos = collect();
        $errors = [];

        foreach ($this->providerAggregator->enabled() as $provider) {
            try {
                $chunk = $mode === self::MODE_SEARCH
                    ? $provider->searchArticles($params)
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
            'mode'      => $mode,
            'keyword'   => $params['keyword'] ?? null,
            'category'  => $params['category'] ?? null,
            'source'    => $params['source'] ?? null,
            'provider'  => $params['provider'] ?? null,
            'author'    => $params['author'] ?? null,
            'from'      => $params['from'] ?? null,
            'to'        => $params['to'] ?? null,
        ];

        return 'news_fetch:' . md5(serialize($keyData));
    }
}
