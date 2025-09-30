<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

/**
 * @OA\Schema(
 *     schema="HeadlinesRequest",
 *     type="object",
 *     title="Headlines Request",
 *     description="Request parameters for fetching top headlines",
 *     @OA\Property(property="category", type="string", example="technology", description="Filter by news category"),
 *     @OA\Property(property="publisher", type="string", example="TechCrunch", description="Filter by publisher name"),
 *     @OA\Property(property="provider", type="string", enum={"newsapi", "guardian", "nyt"}, example="newsapi", description="Filter by news provider"),
 *     @OA\Property(property="author", type="string", example="John Doe", description="Filter by author name"),
 *     @OA\Property(property="from", type="string", format="date", example="2025-09-01", description="Filter articles from this date (YYYY-MM-DD)"),
 *     @OA\Property(property="to", type="string", format="date", example="2025-09-30", description="Filter articles to this date (YYYY-MM-DD)"),
 *     @OA\Property(property="page", type="integer", minimum=1, maximum=100, default=1, example=1, description="Page number for pagination"),
 *     @OA\Property(property="pageSize", type="integer", minimum=1, maximum=100, default=20, example=20, description="Number of articles per page")
 * )
 */

/**
 * Headlines Request Validation
 * 
 * Validates parameters for fetching top headlines from news providers.
 * Based on topHeadlines() method parameters across all providers.
 */
class HeadlinesRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Filters
            'category'  => 'sometimes|string|max:50',
            'publisher' => 'sometimes|string|max:200',
            'provider'  => 'sometimes|string|in:newsapi,guardian,nyt',
            'author'    => 'sometimes|string|max:100',
            
            // Date range (Guardian, NYT support)
            'from'      => 'sometimes|date|date_format:Y-m-d',
            'to'        => 'sometimes|date|date_format:Y-m-d|after_or_equal:from',
            
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
            'provider.in' => 'Provider must be one of: newsapi, guardian, nyt',
            'to.after_or_equal' => 'End date must be after or equal to start date',
        ];
    }
}