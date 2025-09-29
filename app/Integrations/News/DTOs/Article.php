<?php

namespace App\Integrations\News\DTOs;

use Carbon\Carbon;

class Article
{
    public function __construct(
        public string $title,
        public ?string $description,
        public string $url,
        public ?string $imageUrl,
        public ?string $author,
        public ?string $publisher,
        public Carbon $publishedAt,
        public string $provider,
        public ?string $category,
        public ?string $externalId,
        public ?array $metadata,
    ) {}
}
