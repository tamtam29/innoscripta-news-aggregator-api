<?php

namespace App\Integrations\News\Providers;

use App\Integrations\News\Contracts\NewsProvider;
use App\Integrations\News\DTOs\Article;
use App\Integrations\News\Supports\Taxonomy;
use App\Integrations\News\Supports\RateLimitTrait;
use App\Services\SourceService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * NewsAPI.org Provider
 * 
 * Integration with NewsAPI.org for news articles.
 * 
 * @package App\Integrations\News\Providers
 * @see https://newsapi.org/docs
 */
class NewsApiProvider implements NewsProvider
{
    use RateLimitTrait;

    /**
     * NewsAPI.org API key
     * 
     * @var string|null
     */
    protected ?string $apiKey;

    public function __construct(
        private SourceService $sourceService
    ) { 
        // Load API key from config, log warning if missing
        $this->apiKey = config('news.newsapi.key');
        
        if (empty($this->apiKey)) {
            Log::warning('[NewsApiProvider] Missing API key configuration. NewsAPI integration will be skipped.');
        }
    }

    /**
     * Check if the provider is properly configured
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
    
    /**
     * Get provider key
     * 
     * @return string
     */
    public static function key(): string 
    { 
        return 'newsapi'; 
    }

    /**
     * Fetch top headlines sorted by popularity
     * 
     * @param array $params Request parameters for filtering headlines
     * @return Collection Collection of Article DTOs
     */
    public function topHeadlines(array $params = []): Collection
    {
        $queryParams = $this->buildTopHeadlinesParams($params);
        return $this->fetchArticles('top-headlines', $queryParams, $params['category'] ?? null);
    }

    /**
     * Search all articles sorted by publish date
     * 
     * @param array $params Search parameters including keyword
     * @return Collection Collection of Article DTOs
     */
    public function searchArticles(array $params = []): Collection
    {
        $queryParams = $this->buildEverythingParams($params);
        return $this->fetchArticles('everything', $queryParams);
    }

    /**
     * Resolve source ID from source name
     * 
     * @param string|null $sourceName Source name to resolve
     * @return string|null NewsAPI source ID or null if not found
     */
    private function resolveSourceId(?string $sourceName): ?string
    {
        if (!$sourceName) {
            return null;
        }

        $sourceId = $this->sourceService->getSourceIdByName($sourceName);

        if (!$sourceId) {
            Log::warning('[NewsApiProvider] Source not found in NewsAPI sources', [
                'source' => $sourceName
            ]);
        }

        return $sourceId;
    }

    /**
     * Build query parameters for top-headlines endpoint
     * 
     * @param array $params Request parameters
     * @return array Filtered query parameters for API call
     */
    private function buildTopHeadlinesParams(array $params): array
    {
        $sourceId = $this->resolveSourceId($params['source'] ?? null);

        return array_filter([
            'category' => $params['category'] ?? null,
            'sources'  => $sourceId,
            'page'     => $params['page'] ?? 1,
            'pageSize' => $params['pageSize'] ?? 20,
            'sortBy'   => 'popularity',
            'country'  => $sourceId ? null : 'us', // Don't use country filter with sources
            'apiKey'   => $this->apiKey,
        ]);
    }

    /**
     * Build query parameters for everything endpoint
     * 
     * @param array $params Request parameters
     * @return array Filtered query parameters for API call
     */
    private function buildEverythingParams(array $params): array
    {
        $sourceId = $this->resolveSourceId($params['source'] ?? null);

        return array_filter([
            'q'        => $params['keyword'] ?? null,
            'sources'  => $sourceId,
            'from'     => $params['from'] ?? null,
            'to'       => $params['to'] ?? null,
            'page'     => $params['page'] ?? 1,
            'pageSize' => $params['pageSize'] ?? 20,
            'sortBy'   => 'publishedAt',
            'language' => 'en',
            'apiKey'   => $this->apiKey,
        ]);
    }

    /**
     * Execute API request with rate limiting and error handling
     * 
     * @param string $endpoint API endpoint to call
     * @param array $params Query parameters for the request
     * @param string|null $category Optional category for articles
     * @return Collection Collection of Article DTOs
     */
    private function fetchArticles(string $endpoint, array $params, ?string $category = null): Collection
    {
        // Check configuration and rate limits
        if (!$this->isConfigured()) {
            return collect();
        }

        // Check rate limits
        if ($this->isRateLimited()) {
            Log::warning('[NewsApiProvider] Request blocked due to rate limiting');
            return collect();
        }

        // Apply throttling and increment counters
        $this->throttleRequest();
        $this->incrementRateLimit();

        try {
            $response = Http::baseUrl(config('news.newsapi.base'))
                ->get($endpoint, $params)
                ->throw();

            $articles = data_get($response->json(), 'articles', []);
            Log::info('[NewsApiProvider] ' . $endpoint . ' fetched ' . count($articles) . ' articles');
                
            return $this->formatArticles($articles, $category);
        } catch (\Exception $e) {
            Log::error('[NewsApiProvider] ' . $endpoint . ' request failed', [
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * Transform API response into Article DTOs
     * 
     * @param array $articles Raw articles from API response
     * @param string|null $category Optional category for articles
     * @return Collection Collection of Article DTOs
     */
    private function formatArticles(array $articles, ?string $category = null): Collection
    {
        return collect($articles)->map(fn($article) => $this->createArticle($article, $category));
    }

    /**
     * Create Article DTO from NewsAPI data
     * 
     * @param array $article Raw article data from API
     * @param string|null $category Optional category for the article
     * @return Article Article DTO instance
     */
    private function createArticle(array $article, ?string $category = null): Article
    {
        return new Article(
            title: $article['title'] ?? '(no title)',
            description: $article['description'] ?? null,
            url: $article['url'] ?? null,
            imageUrl: $article['urlToImage'] ?? null,
            author: $article['author'] ?? null,
            source: data_get($article, 'source.name') ?? 'NewsAPI',
            publishedAt: $article['publishedAt'] ? Carbon::parse($article['publishedAt']) : null,
            provider: self::key(),
            category: Taxonomy::canonicalizeCategory($category),
            externalId: $article['url'] ?? null,
            metadata: $article
        );
    }

    /**
     * Fetch all available sources from NewsAPI
     * 
     * Used for seeding/updating source database
     * 
     * @return Collection Collection of source data
     */
    public function fetchSources(): Collection
    {
        if (!$this->isConfigured()) {
            Log::warning('[NewsApiProvider] Cannot fetch sources - API key not configured');
            return collect();
        }

        try {
            $response = Http::baseUrl(config('news.newsapi.base'))
                ->get('top-headlines/sources', [
                    'apiKey' => $this->apiKey,
                ])
                ->throw();

            $sources = data_get($response->json(), 'sources', []);
            Log::info('[NewsApiProvider] Fetched ' . count($sources) . ' sources from API');
            
            return collect($sources);
        } catch (\Exception $e) {
            Log::error('[NewsApiProvider] Failed to fetch sources', [
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }
}
