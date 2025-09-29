<?php

namespace App\Services;

use App\Repositories\EloquentArticleRepository;
use App\Integrations\News\ProviderAggregator;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class NewsService
{
    public const MODE_HEADLINES = 'headlines';
    public const MODE_SEARCH    = 'search';

    public function __construct(
        private EloquentArticleRepository $articleRepository,
        private ProviderAggregator $providerAggregator,
    ) {}

    public function search(array $params, int $page, int $pageSize): LengthAwarePaginator
    {
        $mode = !empty($params['keyword']) ? self::MODE_SEARCH : self::MODE_HEADLINES;

        $paginator = $this->articleRepository->search($params, $page, $pageSize);

        $freshMinutes = (int) config('news.freshness.' . ($mode === self::MODE_SEARCH ? 'search_minutes' : 'headlines_minutes'));
        $newest = $this->articleRepository->newestPublishedAt($params);
        $isStale = !$newest || $newest->lt(Carbon::now()->subMinutes($freshMinutes));

        if ($paginator->total() === 0 || $isStale) {
            $dtos = collect();
            foreach ($this->providerAggregator->enabled() as $provider) {
                $chunk = $mode === self::MODE_SEARCH
                    ? $provider->everything(array_merge($params, ['page' => $page,'pageSize' => $pageSize]))
                    : $provider->topHeadlines(array_merge($params, ['page' => $page,'pageSize' => $pageSize]));
                $dtos = $dtos->merge($chunk);
            }
            if ($dtos->isNotEmpty()) $this->articleRepository->upsertFromDTOs($dtos->all());

            $paginator = $this->articleRepository->search($params, $page, $pageSize);
        }

        return $paginator;
    }
}
