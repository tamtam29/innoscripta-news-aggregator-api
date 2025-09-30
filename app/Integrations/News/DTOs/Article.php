<?php

namespace App\Integrations\News\DTOs;

use Carbon\Carbon;

/**
 * Article Data Transfer Object
 *
 * Represents a standardized news article structure that normalizes data
 * from different news providers into a consistent format for the application.
 *
 * @package App\Integrations\News\DTOs
 */
class Article
{
    /**
     * Create a new Article instance
     *
     * @param string $title The article headline/title
     * @param string|null $description Brief description or excerpt of the article
     * @param string $url Direct link to the full article
     * @param string|null $imageUrl URL to the article's featured image
     * @param string|null $author Author name or byline
     * @param string|null $source Publication name (e.g., "The Guardian", "BBC News")
     * @param Carbon $publishedAt When the article was published
     * @param string $provider Source provider key (e.g., 'newsapi', 'guardian', 'nyt')
     * @param string|null $category Normalized category (e.g., 'technology', 'business')
     * @param string|null $externalId Unique identifier from the source provider
     * @param array|null $metadata Raw response data from the provider for debugging
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public string $url,
        public ?string $imageUrl,
        public ?string $author,
        public ?string $source,
        public Carbon $publishedAt,
        public string $provider,
        public ?string $category,
        public ?string $externalId,
        public ?array $metadata,
    ) {
    }
}
