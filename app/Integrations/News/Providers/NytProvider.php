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
 * New York Times API Provider
 *
 * Integration with NYT Article Search and Top Stories APIs.
 * Rate limit: 1000 requests/day, 5 requests/minute.
 *
 * @package App\Integrations\News\Providers
 * @see https://developer.nytimes.com/docs
 */
class NytProvider implements NewsProvider
{
    use RateLimitTrait;

    /**
     * New York Times API key
     *
     * @var string|null
     */
    protected ?string $apiKey;

    public function __construct()
    {
        // Load API key from config, log warning if missing
        $this->apiKey = config('news.nyt.key');

        if (empty($this->apiKey)) {
            Log::warning('[NytProvider] Missing API key configuration. NYT integration will be skipped.');
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
        return 'nyt';
    }

    /**
     * Fetch top stories from NYT
     *
     * @param array $params Filter parameters for top stories
     * @return Collection Collection of Article DTOs
     */
    public function topHeadlines(array $params = []): Collection
    {
        $category = $params['category'] ?? 'home';
        $queryParams = $this->buildTopStoriesParams();
        return $this->fetchArticles("topstories/v2/{$category}.json", $queryParams, 'results');
    }

    /**
     * Search NYT article archive
     *
     * @param array $params Search parameters including keyword and filters
     * @return Collection Collection of Article DTOs
     */
    public function searchArticles(array $params = []): Collection
    {
        $queryParams = $this->buildArticleSearchParams($params);
        return $this->fetchArticles('search/v2/articlesearch.json', $queryParams, 'response.docs');
    }

    /**
     * Build query parameters for top stories endpoint
     *
     * @return array Query parameters for top stories API call
     */
    private function buildTopStoriesParams(): array
    {
        return [
            'api-key' => $this->apiKey,
        ];
    }

    /**
     * Build query parameters for article search endpoint
     *
     * @param array $params Request parameters
     * @return array Formatted query parameters for API call
     */
    private function buildArticleSearchParams(array $params): array
    {
        return array_filter([
            'q'          => $params['keyword'] ?? null,
            'begin_date' => isset($params['from']) ? str_replace('-', '', $params['from']) : null,
            'end_date'   => isset($params['to']) ? str_replace('-', '', $params['to']) : null,
            'page'       => $params['page'] ?? 1,
            'sort'       => 'newest',
            'api-key'    => $this->apiKey,
        ]);
    }

    /**
     * Execute API request with rate limiting and error handling
     */
    private function fetchArticles(string $endpoint, array $params, string $dataPath): Collection
    {
        // Check configuration and rate limits
        if (!$this->isConfigured()) {
            return collect();
        }

        // Check rate limits
        if ($this->isRateLimited()) {
            Log::warning('[NytProvider] Request blocked due to rate limiting');
            return collect();
        }

        // Apply throttling and increment counters
        $this->throttleRequest();
        $this->incrementRateLimit();

        try {
            $response = Http::baseUrl(config('news.nyt.base'))
                ->get($endpoint, $params)
                ->throw();

            $articles = data_get($response->json(), $dataPath, []);
            Log::info('[NytProvider] ' . $endpoint . ' fetched ' . count($articles) . ' articles');

            return $this->formatArticles($articles);
        } catch (\Exception $e) {
            Log::error('[NytProvider] ' . $endpoint . ' request failed', [
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * Transform API response into Article DTOs
     *
     * @param array $articles Raw article data from NYT API
     * @return Collection Collection of formatted Article DTOs
     */
    private function formatArticles(array $articles): Collection
    {
        return collect($articles)->map(fn ($article) => $this->createArticle($article));
    }

    /**
     * Create Article DTO from NYT data
     *
     * @param array $article Raw article data from API
     * @return Article Formatted Article DTO
     */
    private function createArticle(array $article): Article
    {
        return new Article(
            title: $this->extractTitle($article),
            description: $this->extractDescription($article),
            url: $this->extractUrl($article),
            imageUrl: $this->extractImageUrl($article),
            author: $this->extractAuthor($article),
            source: 'The New York Times',
            publishedAt: Carbon::parse($this->extractPublishDate($article)),
            provider: self::key(),
            category: Taxonomy::canonicalizeCategory($this->extractCategory($article)),
            externalId: $this->extractId($article),
            metadata: $article
        );
    }

    /**
     * Extract title from different NYT response formats
     *
     * @param array $article Article data
     * @return string Article title or fallback
     */
    private function extractTitle(array $article): string
    {
        return $article['title'] ?? $article['headline']['main'] ?? '(no title)';
    }

    /**
     * Extract description from different NYT response formats
     *
     * @param array $article Article data
     * @return string|null Article description if available
     */
    private function extractDescription(array $article): ?string
    {
        return $article['abstract'] ?? $article['snippet'] ?? null;
    }

    /**
     * Extract URL from different NYT response formats
     *
     * @param array $article Article data
     * @return string|null Article URL if available
     */
    private function extractUrl(array $article): ?string
    {
        return $article['url'] ?? $article['web_url'] ?? null;
    }

    /**
     * Extract image URL from NYT multimedia data
     *
     * @param array $article Article data with multimedia
     * @return string|null Image URL if available
     */
    private function extractImageUrl(array $article): ?string
    {
        $multimedia = $article['multimedia'] ?? [];

        if (is_array($multimedia) && isset($multimedia['default']['url'])) {
            return $multimedia['default']['url'];
        }

        if (is_array($multimedia)) {
            return collect($multimedia)->firstWhere('format', 'Super Jumbo')['url'] ?? null;
        }

        return null;
    }

    /**
     * Extract author from different NYT response formats
     *
     * @param array $article Article data
     * @return string|null Author name if available
     */
    private function extractAuthor(array $article): ?string
    {
        return $article['byline']['original'] ?? $article['byline'] ?? null;
    }

    /**
     * Extract publish date from different NYT response formats
     *
     * @param array $article Article data
     * @return string Publish date or current time as fallback
     */
    private function extractPublishDate(array $article): string
    {
        return $article['pub_date'] ?? $article['published_date'] ?? now();
    }

    /**
     * Extract category from different NYT response formats
     *
     * @param array $article Article data
     * @return string|null Category name if available
     */
    private function extractCategory(array $article): ?string
    {
        return $article['section_name'] ?? $article['section'] ?? null;
    }

    /**
     * Extract external ID from different NYT response formats
     *
     * @param array $article Article data
     * @return string|null External identifier if available
     */
    private function extractId(array $article): ?string
    {
        return $article['_id'] ?? $article['uri'] ?? $article['url'] ?? null;
    }
}
