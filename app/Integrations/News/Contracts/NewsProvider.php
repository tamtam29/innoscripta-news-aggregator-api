<?php

namespace App\Integrations\News\Contracts;

use App\Integrations\News\DTOs\Article;
use Illuminate\Support\Collection;

interface NewsProvider
{
    /** @return Collection<int,Article> */
    public function topHeadlines(array $params = []): Collection;

    /** @return Collection<int,Article> */
    public function everything(array $params = []): Collection;

    public static function key(): string;
}
