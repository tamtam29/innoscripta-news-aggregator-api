<?php

namespace App\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="ArticleResource",
 *     type="object",
 *     title="Article Resource",
 *     description="Details of a news article",
 *     @OA\Property(property="id", type="integer", example=1, description="Unique identifier of the article"),
 *     @OA\Property(property="title", type="string", example="Breaking: New Technology Breakthrough", description="Article title"),
 *     @OA\Property(property="description", type="string", example="Scientists have made a significant breakthrough in quantum computing...", description="Article description/excerpt"),
 *     @OA\Property(property="published_at", type="string", format="date-time", example="2025-09-30T10:57:36.000000Z", description="Publication date and time"),
 *     @OA\Property(property="author", type="string", example="John Doe", description="Article author name"),
 *     @OA\Property(property="source", type="string", example="TechCrunch", description="Source name"),
 *     @OA\Property(property="provider", type="string", example="newsapi", description="News provider source"),
 *     @OA\Property(property="url", type="string", format="url", example="https://techcrunch.com/article/123", description="Original article URL"),
 *     @OA\Property(property="image_url", type="string", format="url", example="https://images.example.com/article-image.jpg", description="Article image URL"),
 *     @OA\Property(property="category", type="string", example="technology", description="Article category")
 * )
 */
class ArticleResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'description'   => $this->description,
            'published_at'  => $this->published_at,
            'author'        => $this->author,
            'source'        => $this->source?->name,
            'provider'      => $this->provider,
            'url'           => $this->url,
            'image_url'     => $this->image_url,
            'category'      => $this->category,
        ];
    }
}
