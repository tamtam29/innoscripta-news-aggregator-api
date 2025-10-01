<?php

namespace App\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="PreferenceResource",
 *     type="object",
 *     title="Preference Resource",
 *     description="User preference resource representation",
 *     @OA\Property(property="id", type="integer", example=1, description="Unique preference identifier"),
 *     @OA\Property(property="source", type="string", nullable=true, example="BBC News", description="Preferred news source"),
 *     @OA\Property(property="category", type="string", nullable=true, example="technology", description="Preferred news category"),
 *     @OA\Property(property="author", type="string", nullable=true, example="John Smith", description="Preferred author")
 * )
 */
class PreferenceResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'source'       => $this->source,
            'category'     => $this->category,
            'author'       => $this->author
        ];
    }
}
