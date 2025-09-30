<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

/**
 * @OA\Schema(
 *     schema="SearchNewsRequest",
 *     type="object",
 *     title="Search News Request",
 *     description="Request parameters for searching news articles",
 *     required={"keyword"},
 *     @OA\Property(property="keyword", type="string", example="artificial intelligence", description="Search keyword (required)"),
 *     @OA\Property(property="category", type="string", example="technology", description="Filter by news category"),
 *     @OA\Property(property="source", type="string", example="TechCrunch", description="Filter by source name"),
 *     @OA\Property(property="provider", type="string", enum={"newsapi", "guardian", "nyt"}, example="newsapi", description="Filter by news provider"),
 *     @OA\Property(property="author", type="string", example="John Doe", description="Filter by author name"),
 *     @OA\Property(property="from", type="string", format="date", example="2025-09-01", description="Filter articles from this date (YYYY-MM-DD)"),
 *     @OA\Property(property="to", type="string", format="date", example="2025-09-30", description="Filter articles to this date (YYYY-MM-DD)"),
 *     @OA\Property(property="page", type="integer", minimum=1, maximum=100, default=1, example=1, description="Page number for pagination"),
 *     @OA\Property(property="pageSize", type="integer", minimum=1, maximum=100, default=20, example=20, description="Number of articles per page")
 * )
 */

/**
 * Search News Request Validation
 *
 * Validates parameters for searching articles across news providers.
 * Based on everything() method parameters across all providers.
 */
class SearchNewsRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Search term (required for most providers)
            'keyword'   => 'required|string|min:2|max:200',

            // Date range (all providers support)
            'from'      => 'sometimes|date|date_format:Y-m-d',
            'to'        => 'sometimes|date|date_format:Y-m-d|after_or_equal:from',

            // Filters
            'category'  => 'sometimes|string|max:50',
            'source'    => 'sometimes|string|max:200',
            'provider'  => 'sometimes|string|in:newsapi,guardian,nyt',
            'author'    => 'sometimes|string|max:100',

            // Pagination
            'page'      => 'sometimes|integer|min:1|max:100',
            'pageSize'  => 'sometimes|integer|min:1|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'keyword.required' => 'Keyword is required for search',
            'keyword.min' => 'Keyword must be at least 2 characters',
            'provider.in' => 'Provider must be one of: newsapi, guardian, nyt',
            'to.after_or_equal' => 'End date must be after or equal to start date',
        ];
    }
}
