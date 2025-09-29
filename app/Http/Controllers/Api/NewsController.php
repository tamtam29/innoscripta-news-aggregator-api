<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchNewsRequest;
use App\Integrations\News\ProviderAggregator;
use App\Services\NewsService;
use App\Services\NewsPreferenceService;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function __construct(
        private NewsService $newsService,
        private NewsPreferenceService $newsPreferenceService,
        private ProviderAggregator $providerAggregator,
    ) {}

    public function index(SearchNewsRequest $request)
    {
        $params = $request->validated();

        $page     = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['pageSize'] ?? 20);

        $paginator = $this->newsService->search($params, $page, $pageSize);

        return response()->json([
            'data' => $paginator->getCollection(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
            ],
        ]);
    }
}
