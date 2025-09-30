<?php

namespace App\Integrations\News\Providers;

use App\Integrations\News\Contracts\NewsProvider;
use App\Integrations\News\DTOs\Article;
use App\Integrations\News\Supports\Taxonomy;
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
    /**
     * The Guardian API key
     * 
     * @var string|null
     */
    protected ?string $apiKey;

    public function __construct() { 
        $this->apiKey = config('news.guardian.key');
        
        // Log warning if API key is not set (but don't fail during build)
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
     * Get unique identifier for this provider
     * 
     * @return string Provider key 'guardian'
     */
    public static function key(): string { 
        return 'guardian'; 
    }

    /**
     * Fetch top headlines from The Guardian
     * 
     * @param array $params Query parameters:
     *                      - category: Guardian section ID
     *                      - from/to: Date range (YYYY-MM-DD)
     *                      - page: Page number
     *                      - pageSize: Results per page
     * @return Collection<int,Article>
     */
    public function topHeadlines(array $params = []): Collection
    {
        if (!$this->isConfigured()) return collect();

        $params = array_filter([
            'section'     => $params['category'] ?? null,
            'from-date'   => $params['from'] ?? null,
            'to-date'     => $params['to'] ?? null,
            'page'        => $params['page'] ?? 1,
            'page-size'   => $params['pageSize'] ?? 20,
            'show-fields' => 'thumbnail,trailText,byline',
            'order-by'    => 'relevance',
            'api-key'     => $this->apiKey,
        ]);

        try {
            $res = Http::baseUrl(config('news.guardian.base'))
                ->get('search', $params)
                ->throw();

            $results = data_get($res->json(), 'response.results', []);
            Log::info('[GuardianProvider] topHeadlines fetched ' . count($results) . ' articles');
                
            return $this->formatArticles($results);
        } catch (\Exception $e) {
            Log::error('[GuardianProvider] topHeadlines request failed', [
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Search all Guardian content (ordered by newest)
     * 
     * @param array $params Same parameters as topHeadlines
     * @return Collection<int,Article>
     */
    public function everything(array $params = []): Collection
    {
        return $this->topHeadlines(array_merge($params, ['order-by' => 'newest']));
    }

    /**
     * Transform Guardian API response into Article DTOs
     * 
     * Maps Guardian fields to standardized Article structure:
     * webTitle → title, fields.trailText → description, etc.
     * 
     * @param array $articles Raw Guardian API data
     * @param string|null $category Optional category override
     * @return Collection<int,Article>
     */
    private function formatArticles(array $articles, ?string $category = null): Collection
    {
        return collect($articles)->map(function ($article) use ($category) {
            $category = Taxonomy::canonicalizeCategory($article['sectionId'] ?? null);

            return new Article(
                title: $article['webTitle'] ?? '(no title)',
                description: data_get($article, 'fields.trailText'),
                url: $article['webUrl'],
                imageUrl: data_get($article, 'fields.thumbnail'),
                author: data_get($article, 'fields.byline'),
                publisher: 'The Guardian',
                publishedAt: Carbon::parse($article['webPublicationDate'] ?? now()),
                provider: self::key(),
                category: $category,
                externalId: $article['id'] ?? $article['webUrl'] ?? null,
                metadata: $article
            );
        });
    }

}
