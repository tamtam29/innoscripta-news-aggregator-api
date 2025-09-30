<?php

namespace App\Integrations\News\Providers;

use App\Integrations\News\Contracts\NewsProvider;
use App\Integrations\News\DTOs\Article;
use App\Integrations\News\Supports\Taxonomy;
use App\Integrations\News\Supports\RateLimitTrait;
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

    public function __construct() 
    { 
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
     */
    public function topHeadlines(array $params = []): Collection
    {
        $queryParams = $this->buildTopHeadlinesParams($params);
        return $this->fetchArticles('top-headlines', $queryParams, $params['category'] ?? null);
    }

    /**
     * Search all articles sorted by publish date
     */
    public function searchArticles(array $params = []): Collection
    {
        $queryParams = $this->buildEverythingParams($params);
        return $this->fetchArticles('everything', $queryParams);
    }

    /**
     * Build query parameters for top-headlines endpoint
     */
    private function buildTopHeadlinesParams(array $params): array
    {
        return array_filter([
            'category' => $params['category'] ?? null,
            'sources'  => $params['publisher'] ?? null,
            'page'     => $params['page'] ?? 1,
            'pageSize' => $params['pageSize'] ?? 20,
            'sortBy'   => 'popularity',
            'country'  => 'us',
            'apiKey'   => $this->apiKey,
        ]);
    }

    /**
     * Build query parameters for everything endpoint
     */
    private function buildEverythingParams(array $params): array
    {
        return array_filter([
            'q'        => $params['keyword'] ?? null,
            'sources'  => $params['publisher'] ?? null,
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
     */
    private function formatArticles(array $articles, ?string $category = null): Collection
    {
        return collect($articles)->map(fn($article) => $this->createArticle($article, $category));
    }

    /**
     * Create Article DTO from NewsAPI data
     */
    private function createArticle(array $article, ?string $category = null): Article
    {
        return new Article(
            title: $article['title'] ?? '(no title)',
            description: $article['description'] ?? null,
            url: $article['url'] ?? null,
            imageUrl: $article['urlToImage'] ?? null,
            author: $article['author'] ?? null,
            publisher: data_get($article, 'source.name') ?? 'NewsAPI',
            publishedAt: $article['publishedAt'] ? Carbon::parse($article['publishedAt']) : null,
            provider: self::key(),
            category: Taxonomy::canonicalizeCategory($category),
            externalId: $article['url'] ?? null,
            metadata: $article
        );
    }
}
