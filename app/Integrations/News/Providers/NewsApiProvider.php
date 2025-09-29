<?php

namespace App\Integrations\News\Providers;

use App\Integrations\News\Contracts\NewsProvider;
use App\Integrations\News\DTOs\Article;
use App\Integrations\News\Supports\Taxonomy;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewsApiProvider implements NewsProvider
{
    protected string $articlepiKey;

    public function __construct() { 
        $this->apiKey = (string) config('news.newsapi.key'); 
    }
    
    public static function key(): string { 
        return 'newsapi'; 
    }

    public function topHeadlines(array $params = []): Collection
    {
        $params = array_filter([
            'q'        => $params['keyword'] ?? null,
            'country'  => $params['country'] ?? null,
            'category' => $params['category'] ?? null,
            'sources'  => $params['publisher'] ?? null,
            'page'     => $params['page'] ?? 1,
            'pageSize' => $params['pageSize'] ?? 20,
            'apiKey'   => $this->apiKey,
        ]);

        try {
            $res = Http::baseUrl(config('news.newsapi.base'))->get('top-headlines', $params)->throw();
            $json = $res->json();

            $category = Taxonomy::canonicalizeCategory($params['category'] ?? null);

            return $this->formatArticles($json['articles'] ?? [], $category);
        } catch (\Exception $e) {
            Log::error('NewsApiProvider - topHeadlines request failed', [
                'message' => $e->getMessage(),
                'params' => $params
            ]);

            return collect();
        }
    }

    public function everything(array $params = []): Collection
    {
        $params = array_filter([
            'q'        => $params['keyword'] ?? null,
            'from'     => $params['from'] ?? null,
            'to'       => $params['to'] ?? null,
            'sources'  => $params['publisher'] ?? null,
            'language' => $params['language'] ?? 'en',
            'sortBy'   => $params['sortBy'] ?? 'publishedAt',
            'page'     => $params['page'] ?? 1,
            'pageSize' => $params['pageSize'] ?? 20,
            'apiKey'   => $this->apiKey,
        ]);

        try {
            $res = Http::baseUrl(config('news.newsapi.base'))->get('everything', $params)->throw();
            $json = $res->json();

            return $this->formatArticles($json['articles'] ?? []);
        } catch (\Exception $e) {
            Log::error('NewsApiProvider - everything request failed', [
                'message' => $e->getMessage(),
                'params' => $params
            ]);

            return collect();
        }
    }

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
