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
 * NewsAPI.org Provider
 * 
 * Integration with NewsAPI.org for news articles.
 * 
 * @package App\Integrations\News\Providers
 * @see https://newsapi.org/docs
 */
class NewsApiProvider implements NewsProvider
{
    /**
     * NewsAPI.org API key
     * 
     * @var string
     */
    protected string $apiKey;

    public function __construct() { 
        $this->apiKey = (string) config('news.newsapi.key'); 
    }
    
    /**
     * Get provider key
     * 
     * @return string
     */
    public static function key(): string { 
        return 'newsapi'; 
    }

    /**
     * Fetch top headlines from NewsAPI
     * 
     * @param array $params Query parameters:
     *                      - category: Category
     *                      - publisher: Comma-separated news sources
     *                      - page: Page number
     *                      - pageSize: Results per page (max: 100)
     * @return Collection<int,Article>
     */
    public function topHeadlines(array $params = []): Collection
    {
        $params = array_filter([
            'category' => $params['category'] ?? null,
            'sources'  => $params['publisher'] ?? null,
            'page'     => $params['page'] ?? 1,
            'pageSize' => $params['pageSize'] ?? 20,
            'sortBy'   => 'popularity',
            'country'  => 'us',
            'apiKey'   => $this->apiKey,
        ]);

        try {
            $res = Http::baseUrl(config('news.newsapi.base'))
                ->get('top-headlines', $params)
                ->throw();

            $json = $res->json();

            Log::info('[NewsApiProvider] topHeadlines fetched ' . count($json['articles'] ?? []) . ' articles');
            $category = Taxonomy::canonicalizeCategory($params['category'] ?? null);

            return $this->formatArticles($json['articles'] ?? [], $category);
        } catch (\Exception $e) {
            Log::error('[NewsApiProvider] topHeadlines request failed', [
                'message' => $e,
                'params' => $params
            ]);

            return collect();
        }
    }

    /**
     * Search all articles from NewsAPI
     * 
     * @param array $params Query parameters:
     *                      - keyword: Search term (required)
     *                      - publisher: Specific news sources
     *                      - from/to: Date range (YYYY-MM-DD)
     *                      - page: Page number
     *                      - pageSize: Results per page (max: 100)
     * @return Collection<int,Article>
     */
    public function everything(array $params = []): Collection
    {
        $params = array_filter([
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

        try {
            $res = Http::baseUrl(config('news.newsapi.base'))
                ->get('everything', $params)
                ->throw();

            $json = $res->json();
            Log::info('[NewsApiProvider] everything fetched ' . count($json['articles'] ?? []) . ' articles');

            return $this->formatArticles($json['articles'] ?? [], null);
        } catch (\Exception $e) {
            Log::error('[NewsApiProvider] everything request failed', [
                'message' => $e->getMessage(),
                'params' => $params
            ]);

            return collect();
        }
    }

    /**
     * Transform NewsAPI response into Article DTOs
     * 
     * Maps NewsAPI fields to standardized Article structure.
     * 
     * @param array $articles Raw NewsAPI data
     * @param string|null $category Optional category override
     * @return Collection<int,Article>
     */
    private function formatArticles(array $articles, ?string $category = null): Collection
    {
        return collect($articles)->map(function ($article) use ($category) {
            return new Article(
                title: $article['title'] ?? '(no title)',
                description: $article['description'] ?? null,
                url: $article['url'],
                imageUrl: $article['urlToImage'] ?? null,
                author: $article['author'] ?? null,
                publisher: data_get($article, 'source.name') ?? 'NewsAPI',
                publishedAt: $article['publishedAt'] ? Carbon::parse($article['publishedAt']) : null,
                provider: self::key(),
                category: $category,
                externalId: $article['url'] ?? null,
                metadata: $article
            );
        });
    }
}
