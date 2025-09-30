<?php

namespace App\Repositories\Contracts;

use App\Models\Article;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Article Repository Contract
 * 
 * Defines the interface for article data operations
 */
interface ArticleRepository
{
    /**
     * Insert or update articles from DTOs
     * 
     * @param array<int,\App\News\DTOs\Article> $articleDTOs
     * @return Collection
     */
    public function upsertFromDTOs(array $articleDTOs): Collection;

    /**
     * Search articles with filters and pagination
     * 
     * @param array $filters Search and filter parameters
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @return LengthAwarePaginator
     */
    public function search(array $filters, int $page, int $pageSize): LengthAwarePaginator;

    /**
     * Get the newest published date for filtered articles
     * 
     * @param array $filters Filter parameters
     * @return Carbon|null
     */
    public function newestPublishedAt(array $filters): ?Carbon;

    /**
     * Find article by ID
     * 
     * @param int $id Article ID
     * @return Article|null
     */
    public function findById(int $id): ?Article;

    /**
     * Delete article by ID
     * 
     * @param int $id Article ID
     * @return bool True if deleted, false if not found
     */
    public function deleteById(int $id): bool;
}
