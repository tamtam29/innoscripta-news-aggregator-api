<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

/**
 * @OA\Schema(
 *     schema="PreferenceRequest",
 *     type="object",
 *     title="Preference Request",
 *     description="Request body for updating user preferences",
 *     @OA\Property(property="source", type="string", maxLength=200, example="BBC News", description="Preferred news source (max 200 characters)"),
 *     @OA\Property(property="category", type="string", maxLength=50, example="technology", description="Preferred news category (max 50 characters)"),
 *     @OA\Property(property="author", type="string", maxLength=100, example="John Smith", description="Preferred author (max 100 characters)")
 * )
 */

/**
 * Preference Request Validation
 *
 * Validates parameters for updating user preferences.
 * All fields are optional and will be validated if provided.
 */
class PreferenceRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source'    => 'sometimes|string|max:200',
            'category'  => 'sometimes|string|max:50',
            'author'    => 'sometimes|string|max:100',
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
            'source.string'    => 'The source must be a valid string.',
            'source.max'       => 'The source may not be greater than 200 characters.',
            'category.string'  => 'The category must be a valid string.',
            'category.max'     => 'The category may not be greater than 50 characters.',
            'author.string'    => 'The author must be a valid string.',
            'author.max'       => 'The author may not be greater than 100 characters.',
        ];
    }
}
