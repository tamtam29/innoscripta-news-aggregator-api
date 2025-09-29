<?php

namespace App\Repositories\Contracts;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ArticleRepository
{
    /** @param array<int,\App\News\DTOs\Article> $articleDTOs */
    public function upsertFromDTOs(array $articleDTOs): Collection;
    public function search(array $filters, int $page, int $pageSize): LengthAwarePaginator;
    public function newestPublishedAt(array $filters): ?Carbon;
}
