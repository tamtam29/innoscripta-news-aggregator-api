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
 * The Guardian API Provider
 *
 * Integration with The Guardian's Open Platform API.
 *
 * @package App\Integrations\News\Providers
 * @see https://open-platform.theguardian.com/documentation/
 */
class GuardianProvider implements NewsProvider
{
    use RateLimitTrait;

    /**
     * The Guardian API key
     *
     * @var string|null
     */
    protected ?string $apiKey;

    public function __construct()
    {
        // Load API key from config, log warning if missing
        $this->apiKey = config('news.guardian.key');

        if (empty($this->apiKey)) {
            Log::warning('[GuardianProvider] Missing API key configuration. Guardian integration will be skipped.');
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
        return 'guardian';
    }

    /**
     * Fetch top headlines ordered by relevance
     */
    public function topHeadlines(array $params = []): Collection
    {
        $queryParams = $this->buildQueryParams($params, 'newest');
        return $this->fetchArticles('search', $queryParams);
    }

    /**
     * Search all content ordered by newest
     */
    public function searchArticles(array $params = []): Collection
    {
        $queryParams = $this->buildQueryParams($params, 'relevance');
        return $this->fetchArticles('search', $queryParams);
    }

    /**
     * Build Guardian API query parameters
     */
    private function buildQueryParams(array $params, string $orderBy): array
    {
        return array_filter([
            'section'     => $params['category'] ?? null,
            'from-date'   => $params['from'] ?? null,
            'to-date'     => $params['to'] ?? null,
            'page'        => $params['page'] ?? 1,
            'page-size'   => $params['pageSize'] ?? 20,
            'show-fields' => 'thumbnail,trailText,byline',
            'order-by'    => $orderBy,
            'api-key'     => $this->apiKey,
        ]);
    }

    /**
     * Execute API request with rate limiting and error handling
     */
    private function fetchArticles(string $endpoint, array $params): Collection
    {
        // Check configuration
        if (!$this->isConfigured()) {
            return collect();
        }

        // Check rate limits
        if ($this->isRateLimited()) {
            Log::warning('[GuardianProvider] Request blocked due to rate limiting');
            return collect();
        }

        // Apply throttling and increment counters
        $this->throttleRequest();
        $this->incrementRateLimit();

        try {
            $response = Http::baseUrl(config('news.guardian.base'))
                ->get($endpoint, $params)
                ->throw();

            $results = data_get($response->json(), 'response.results', []);
            Log::info('[GuardianProvider] ' . $endpoint . ' fetched ' . count($results) . ' articles');

            return $this->formatArticles($results);
        } catch (\Exception $e) {
            Log::error('[GuardianProvider] ' . $endpoint . ' request failed', [
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * Transform API response into Article DTOs
     */
    private function formatArticles(array $articles): Collection
    {
        return collect($articles)->map(fn ($article) => $this->createArticle($article));
    }

    /**
     * Create Article DTO from Guardian data
     */
    private function createArticle(array $article): Article
    {
        return new Article(
            title: $article['webTitle'] ?? '(no title)',
            description: data_get($article, 'fields.trailText'),
            url: $article['webUrl'] ?? null,
            imageUrl: data_get($article, 'fields.thumbnail'),
            author: data_get($article, 'fields.byline'),
            source: 'The Guardian',
            publishedAt: Carbon::parse($article['webPublicationDate'] ?? now()),
            provider: self::key(),
            category: Taxonomy::canonicalizeCategory($article['sectionId'] ?? null),
            externalId: $article['id'] ?? $article['webUrl'] ?? null,
            metadata: $article
        );
    }
}
