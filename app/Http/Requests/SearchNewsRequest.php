<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

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
            'keyword'   => 'sometimes|string|min:2',
            'from'      => 'sometimes|date',
            'to'        => 'sometimes|date|after_or_equal:from',
            'category'  => 'sometimes',
            'publisher' => 'sometimes',
            'provider'  => 'sometimes',
            'author'    => 'sometimes',
            'page'      => 'sometimes|integer|min:1',
            'pageSize'  => 'sometimes|integer|min:1|max:100',
        ];
    }
}
