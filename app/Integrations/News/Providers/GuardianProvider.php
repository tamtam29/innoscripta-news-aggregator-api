<?php

namespace App\Integrations\News\Providers;

use App\Integrations\News\Contracts\NewsProvider;
use App\Integrations\News\DTOs\Article;
use App\Integrations\News\Supports\Taxonomy;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GuardianProvider implements NewsProvider
{
    protected string $apiKey;

    public function __construct() { 
        $this->apiKey = (string) config('news.guardian.key'); 
    }
    
    public static function key(): string { 
        return 'guardian'; 
    }

    public function topHeadlines(array $params = []): Collection
    {
        $params = array_filter([
            'q'           => $params['keyword'] ?? null,
            'section'     => $params['category'] ?? null,
            'from-date'   => $params['from'] ?? null,
            'to-date'     => $params['to'] ?? null,
            'order-by'    => $params['order'] ?? 'relevance',
            'show-fields' => 'thumbnail,trailText,byline',
            'page'        => $params['page'] ?? 1,
            'page-size'   => $params['pageSize'] ?? 20,
            'api-key'     => $this->apiKey,
        ]);

        try {
            $res = Http::baseUrl(config('news.guardian.base'))
                ->get('search', $params)
                ->throw();

            $results = data_get($res->json(), 'response.results', []);

            return $this->formatArticles($results);
        } catch (\Exception $e) {
            Log::error('GuardianProvider - topHeadlines request failed', [
                'message' => $e->getMessage(),
                'params' => $params
            ]);

            return collect();
        }
    }

    public function everything(array $params = []): Collection
    {
        return $this->topHeadlines(array_merge($params, ['order-by' => 'newest']));
    }

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
