<?php

namespace App\Repositories;

use App\Models\Article;
use App\Models\ArticleSource;
use App\Models\Source;
use App\Repositories\Contracts\ArticleRepository;
use App\Integrations\News\DTOs\Article as ArticleDTO;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Eloquent Article Repository
 * 
 * Database repository implementation for managing articles using Laravel's Eloquent ORM.
 * Handles article storage, retrieval, and search operations with provider integration.
 * 
 * @package App\Repositories
 */
class EloquentArticleRepository implements ArticleRepository
{
    /**
     * Find article by ID
     * 
     * @param int $id Article ID
     * @return Article|null
     */
    public function findById(int $id): ?Article
    {
        return Article::find($id);
    }

    /**
     * Delete article by ID
     * 
     * @param int $id Article ID
     * @return bool True if deleted, false if not found
     */
    public function deleteById(int $id): bool
    {
        $article = Article::find($id);
        
        if (!$article) {
            return false;
        }
        
        return $article->delete();
    }

    /**
     * Insert or update articles from DTOs
     * 
     * Converts Article DTOs to database records and performs upsert operations.
     * Also creates/updates associated ArticleSource records for provider tracking.
     * 
     * @param array $articleDTOs Array of ArticleDTO objects
     * @return Collection<int,Article> Collection of persisted Article models
     */
    public function upsertFromDTOs(array $articleDTOs): Collection
    {
        Log::info('[EloquentArticleRepository] Upserting ' . count($articleDTOs) . ' articles from DTOs');
        if (empty($articleDTOs)) return collect();

        $now = now();
        $articleRows = [];
        $articleSourceRows = [];

        foreach ($articleDTOs as $dto) {
            /** @var ArticleDTO $dto */
            $sourceId = null;
            if (!empty($dto->source)) {
                $source = Source::where('name', $dto->source)->first();
                if ($source) {
                    $sourceId = $source->id;
                }
            }

            $articleRows[] = [
                'url_sha1'     => sha1($dto->url),
                'title'        => $dto->title,
                'description'  => $dto->description,
                'url'          => $dto->url,
                'image_url'    => $dto->imageUrl,
                'author'       => $dto->author,
                'source_id'    => $sourceId,
                'published_at' => $dto->publishedAt,
                'provider'     => $dto->provider,
                'category'     => $dto->category,
            ];

            if ($dto->provider) {
                $articleSourceRows[] = [
                    'provider'    => $dto->provider,
                    'external_id' => $dto->externalId ?? $dto->url,
                    'metadata'    => $dto->metadata,
                    'url_sha1'    => sha1($dto->url),
                ];
            }
        }

        DB::transaction(function () use ($articleRows, $articleSourceRows) {
            Article::upsert(
                $articleRows,
                ['url_sha1'],
                ['title','description','url','image_url','author','source_id','published_at','provider','category']
            );

            $articles = Article::whereIn('url_sha1', array_column($articleRows, 'url_sha1'))
                ->pluck('id','url_sha1');

            if (!empty($articleSourceRows)) {
                $toUpsert = array_map(function ($source) use ($articles) {
                    return [
                        'article_id'  => $articles[$source['url_sha1']] ?? null,
                        'provider'    => $source['provider'],
                        'external_id' => $source['external_id'],
                        'metadata'    => json_encode($source['metadata']),
                    ];
                }, $articleSourceRows);

                $toUpsert = array_values(array_filter($toUpsert, fn($r) => !empty($r['article_id'])));

                ArticleSource::upsert(
                    $toUpsert,
                    ['provider','external_id'],
                    ['article_id','metadata']
                );
            }
        });

        return Article::whereIn('url_sha1', array_column($articleRows, 'url_sha1'))->get();
    }

    /**
     * Build base query with filters
     * 
     * Constructs Eloquent query builder with common filtering logic.
     * 
     * @param array $filters
     * @return Builder Eloquent query builder with applied filters
     */
    private function baseQuery(array $filters): Builder
    {
        $query = Article::with(['source']);

        if (!empty($filters['keyword'])) {
            $term = trim($filters['keyword']);
            $query->where(function (Builder $w) use ($term) {
                $w->where('title', 'ilike', "%{$term}%")
                    ->orWhere('description', 'ilike', "%{$term}%")
                    ->orWhere('author', 'ilike', "%{$term}%")
                    ->orWhere(function ($subQuery) use ($term) {
                        $subQuery->whereHas('source', function ($query) use ($term) {
                            $query->where('name', 'ilike', "%{$term}%");
                        });
                    });
            });
        }

        if (!empty($filters['from'])) { 
            $query->where('published_at', '>=', $filters['from']); 
        }

        if (!empty($filters['to'])) {
            $query->where('published_at', '<=', $filters['to']);
        }

        if (!empty($filters['provider'])) {
            $providers = is_array($filters['provider']) ? $filters['provider'] : [$filters['provider']];
            $query->whereIn('provider', $providers);
        }

        if (!empty($filters['source'])) {
            $sources = is_array($filters['source']) ? $filters['source'] : [$filters['source']];
            $query->whereHas('source', function ($q) use ($sources) {
                $q->where(function ($subQuery) use ($sources) {
                    foreach ($sources as $source) {
                        $subQuery->orWhere('name', 'ilike', "%{$source}%");
                    }
                });
            });
        }

        if (!empty($filters['author'])) {
            $authors = is_array($filters['author']) ? $filters['author'] : [$filters['author']];
            $query->where(function($w) use ($authors) {
                foreach ($authors as $author) $w->orWhere('author', 'ilike', '%'.$author.'%');
            });
        }

        if (!empty($filters['category'])) {
            $categories = is_array($filters['category']) ? $filters['category'] : [$filters['category']];
            $query->whereIn('category', $categories);
        }

        return $query->orderByDesc('published_at')->orderByDesc('id');
    }

    /**
     * Search articles with pagination
     * 
     * @param array $filters Filter parameters (see baseQuery)
     * @param int $page Page number
     * @param int $pageSize Number of results per page
     * @return LengthAwarePaginator Paginated article results
     */
    public function search(array $filters, int $page, int $pageSize): LengthAwarePaginator
    {
        return $this->baseQuery($filters)->paginate($pageSize, ['*'], 'page', $page);
    }
}
