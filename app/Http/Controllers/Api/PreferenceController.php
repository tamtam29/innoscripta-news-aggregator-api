<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PreferenceService;
use App\Http\Resources\PreferenceResource;
use App\Http\Requests\PreferenceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Preference API Controller
 *
 * Handles preference retrieval and update endpoints.
 * Uses singleton approach (no user authentication required).
 *
 * @package App\Http\Controllers\Api
 */
class PreferenceController extends Controller
{
    private PreferenceService $preferenceService;

    public function __construct(PreferenceService $preferenceService)
    {
        $this->preferenceService = $preferenceService;
    }

    /**
     * @OA\Get(
     *     path="/api/preferences",
     *     operationId="getPreferences",
     *     tags={"Preferences"},
     *     summary="Get current user preferences",
     *     description="Retrieve the current preference settings for news filtering. Returns the singleton preference record containing preferred source, category, and author. These preferences are automatically applied when fetching news without explicit filters.",
     *     @OA\Response(
     *         response=200,
     *         description="Preferences retrieved successfully",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/PreferenceResource"
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $preference = $this->preferenceService->getPreference();
        return response()->json(new PreferenceResource($preference));
    }

    /**
     * @OA\Put(
     *     path="/api/preferences",
     *     operationId="updatePreferences",
     *     tags={"Preferences"},
     *     summary="Update user preferences",
     *     description="Create or update user preferences for automatic news filtering. These preferences will be applied when fetching news without explicit filter parameters. Request parameters in news endpoints will override these preferences.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Preference data to update. All fields are optional.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="source",
     *                 type="string",
     *                 description="Preferred news source (max 200 characters)",
     *                 maxLength=200,
     *                 example="BBC News"
     *             ),
     *             @OA\Property(
     *                 property="category",
     *                 type="string",
     *                 description="Preferred news category (max 50 characters)",
     *                 maxLength=50,
     *                 example="technology"
     *             ),
     *             @OA\Property(
     *                 property="author",
     *                 type="string",
     *                 description="Preferred author (max 100 characters)",
     *                 maxLength=100,
     *                 example="John Smith"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preferences updated successfully",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/PreferenceResource"
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "source": {"The source field must be a string."},
     *                     "category": {"The category field must be a string."}
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function update(PreferenceRequest $request): JsonResponse
    {
        $params = $request->validated();
        $preference = $this->preferenceService->updatePreferences($params);
        return response()->json(new PreferenceResource($preference));
    }
}
