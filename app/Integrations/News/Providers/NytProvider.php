<?php

namespace App\Integrations\News\Providers;

use App\Integrations\News\Contracts\NewsProvider;
use App\Integrations\News\DTOs\Article;
use App\Integrations\News\Supports\Taxonomy;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NytProvider implements NewsProvider
{
    protected string $apiKey;

    public function __construct() { 
        $this->apiKey = (string) config('news.nyt.key'); 
    }

    public static function key(): string { 
        return 'nyt'; 
    }

    public function topHeadlines(array $params = []): Collection
    {
        $category = $params['category'] ?? 'home';
        $params = array_filter([
            'api-key' => $this->apiKey,
        ]);

        try {
            $res = Http::baseUrl(config('news.nyt.base'))
                ->get("topstories/v2/{$category}.json", $params)
                ->throw();

            $results = $res->json()['results'] ?? [];

            return $this->formatArticles($results);
        } catch (\Exception $e) {
            Log::error('NytProvider - topHeadlines request failed', [
                'message' => $e->getMessage(),
                'params' => $params
            ]);

            return collect();
        }
    }

    public function everything(array $params = []): Collection
    {
        $params = array_filter([
            'q'          => $params['keyword'] ?? null,
            'begin_date' => isset($params['from']) ? str_replace('-', '', $params['from']) : null,
            'end_date'   => isset($params['to']) ? str_replace('-', '', $params['to']) : null,
            'page'       => max(0, ($params['page'] ?? 1) - 1),
            'api-key'    => $this->apiKey,
        ]);

        try {
            $res = Http::baseUrl(config('news.nyt.base'))
                ->get('search/v2/articlesearch.json', $params)
                ->throw();

            $docs = data_get($res->json(), 'response.docs', []);

            return $this->formatArticles($docs);
        } catch (\Exception $e) {
            Log::error('NytProvider - everything request failed', [
                'message' => $e->getMessage(),
                'params' => $params
            ]);

            return collect();
        }
    }

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
