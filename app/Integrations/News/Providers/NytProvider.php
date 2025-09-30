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
    /**
     * New York Times API key
     * 
     * @var string|null
     */
    protected ?string $apiKey;

    public function __construct() { 
        $this->apiKey = config('news.nyt.key');
        
        // Log warning if API key is not set (but don't fail during build)
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
    public static function key(): string { 
        return 'nyt'; 
    }

    /**
     * Fetch top stories from NYT
     * 
     * @param array $params Query parameters:
     *                      - category: NYT section (home, world, business, etc.)
     * @return Collection<int,Article>
     */
    public function topHeadlines(array $params = []): Collection
    {
        if (!$this->isConfigured()) return collect();

        $category = $params['category'] ?? 'home';
        $params = array_filter([
            'api-key' => $this->apiKey,
        ]);

        try {
            $res = Http::baseUrl(config('news.nyt.base'))
                ->get("topstories/v2/{$category}.json", $params)
                ->throw();

            $results = $res->json()['results'] ?? [];
            Log::info('[NytProvider] topHeadlines fetched ' . count($results) . ' articles');

            return $this->formatArticles($results);
        } catch (\Exception $e) {
            Log::error('[NytProvider] topHeadlines request failed', [
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Search NYT article archive
     * 
     * @param array $params Query parameters:
     *                      - keyword: Search term
     *                      - from/to: Date range (YYYY-MM-DD)
     *                      - page: Page number
     * @return Collection<int,Article>
     */
    public function everything(array $params = []): Collection
    {
        if (!$this->isConfigured()) return collect();

        $params = array_filter([
            'q'          => $params['keyword'] ?? null,
            'begin_date' => isset($params['from']) ? str_replace('-', '', $params['from']) : null,
            'end_date'   => isset($params['to']) ? str_replace('-', '', $params['to']) : null,
            'page'       => max(0, ($params['page'] ?? 1) - 1),
            'sort'       => 'newest',
            'api-key'    => $this->apiKey,
        ]);

        try {
            $res = Http::baseUrl(config('news.nyt.base'))
                ->get('search/v2/articlesearch.json', $params)
                ->throw();

            $docs = data_get($res->json(), 'response.docs', []);
            Log::info('[NytProvider] everything fetched ' . count($docs) . ' articles');

            return $this->formatArticles($docs);
        } catch (\Exception $e) {
            Log::error('[NytProvider] everything request failed', [
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Transform NYT API response into Article DTOs
     * 
     * Handles both Top Stories and Article Search response formats.
     * Maps NYT fields to standardized Article structure.
     * 
     * @param array $articles Raw NYT API data
     * @return Collection<int,Article>
     */
    private function formatArticles(array $articles): Collection
    {
        return collect($articles)->map(function ($article) {
            $category = Taxonomy::canonicalizeCategory($article['section_name'] ?? $article['section'] ?? null);
            $multimedia = $article['multimedia'] ?? [];
            $imageUrl = null;

            if (is_array($multimedia) && isset($multimedia['default']['url'])) {
                $imageUrl = $multimedia['default']['url'];
            } elseif (is_array($multimedia)) {
                $imageUrl = collect($multimedia)->firstWhere('format', 'Super Jumbo')['url'] ?? null;
            }

            return new Article(
                title: $article['title'] ?? $article['headline']['main'] ?? '(no title)',
                description: $article['abstract'] ?? $article['snippet'] ?? null,
                url: $article['url'] ?? $article['web_url'] ?? null,
                imageUrl: $imageUrl,
                author: $article['byline']['original'] ?? $article['byline'] ?? null,
                publisher: 'The New York Times',
                publishedAt: Carbon::parse($article['pub_date'] ?? $article['published_date'] ?? now()),
                provider: self::key(),
                category: $category,
                externalId: $article['_id'] ?? $article['uri'] ?? $article['url'] ?? null,
                metadata: $article
            );
        });
    }
}
