<?php

namespace App\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

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
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'published_at' => $this->published_at,
            'author' => $this->author,
            'publisher' => $this->publisher,
            'provider' => $this->provider,
            'url' => $this->url,
            'image_url' => $this->image_url,
            'category' => $this->category,
        ];
    }
}
