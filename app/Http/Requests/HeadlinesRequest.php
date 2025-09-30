<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

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