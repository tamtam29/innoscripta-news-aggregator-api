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
 * 
 * @package App\Repositories\Contracts
 */
interface ArticleRepository
{
    /**
     * Insert or update articles from DTOs
     * 
     * @param array $articleDTOs Array of Article DTOs
     * @return Collection Collection of persisted Article models
     */
    public function upsertFromDTOs(array $articleDTOs): Collection;

    /**
     * Search articles with filters and pagination
     * 
     * @param array $filters Search and filter parameters
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @return LengthAwarePaginator Paginated article results
     */
    public function search(array $filters, int $page, int $pageSize): LengthAwarePaginator;

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
