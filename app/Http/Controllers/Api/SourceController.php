<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SourceCollection;
use App\Services\SourceService;
use Illuminate\Http\Request;

/**
 * Source API Controller
 * 
 * Handles source retrieval endpoints with pagination and search functionality.
 * 
 * @package App\Http\Controllers\Api
 */
class SourceController extends Controller
{
    public function __construct(private SourceService $sourceService) {}

    /**
     * @OA\Get(
     *     path="/sources",
     *     summary="List sources",
     *     description="Retrieve a list of sources",
     *     tags={"Sources"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with sources",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="source_id", type="string", example="abc-news", description="Unique identifier of the source"),
     *                 @OA\Property(property="source_name", type="string", example="ABC News", description="Name of the source")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $sources = $this->sourceService->getAllSourceNames();
        return response()->json($sources);
    }
}