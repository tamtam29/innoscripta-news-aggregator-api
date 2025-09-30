<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HeadlinesRequest;
use App\Http\Requests\SearchNewsRequest;
use App\Http\Resources\ArticleCollection;
use App\Http\Resources\ArticleResource;
use App\Services\NewsService;
use App\Services\NewsPreferenceService;

/**
 * News API Controller
 * 
 * Handles news article retrieval endpoints for headlines and search.
 * 
 * @package App\Http\Controllers\Api
 */
class NewsController extends Controller
{
    public function __construct(
        private NewsService $newsService,
        private NewsPreferenceService $newsPreferenceService,
    ) {}

    /**
     * Get top headlines
     * 
     * @param HeadlinesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function headlines(HeadlinesRequest $request)
    {
        $params = $request->validated();
        
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['pageSize'] ?? 20);

        $paginator = $this->newsService->getHeadlines($params, $page, $pageSize);

        return $this->formatResponse($paginator);
    }

    /**
     * Search articles
     * 
     * @param SearchNewsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(SearchNewsRequest $request)
    {
        $params = $request->validated();
        
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['pageSize'] ?? 20);

        $paginator = $this->newsService->searchArticles($params, $page, $pageSize);

        return $this->formatResponse($paginator);
    }

    /**
     * Format paginated response
     * 
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator
     * @return \Illuminate\Http\JsonResponse
     */
    private function formatResponse($paginator)
    {
        return response()->json([
            'data' => new ArticleCollection($paginator->getCollection()),
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

    /**
     * Show a specific article
     * 
     * @param int $id Article ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $result = $this->newsService->findById($id);   
        return response()->json(new ArticleResource($result));
    }

    /**
     * Delete a specific article
     * 
     * @param int $id Article ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $deleted = $this->newsService->deleteById($id);
        
        return response()->json([
            'message' => 'Article deleted successfully',
            'data' => ['id' => $id]
        ]);
    }
}
