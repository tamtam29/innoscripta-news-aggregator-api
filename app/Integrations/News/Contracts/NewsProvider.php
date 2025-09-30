<?php

namespace App\Integrations\News\Contracts;

use App\Integrations\News\DTOs\Article;
use Illuminate\Support\Collection;

/**
 * News Provider Contract
 * 
 * Defines the interface that all news providers must implement to ensure
 * consistent behavior across different news APIs (NewsAPI, Guardian, NYT, etc.)
 * 
 * @package App\Integrations\News\Contracts
 */
interface NewsProvider
{
    /**
     * Check if the provider is properly configured
     * 
     * @return bool True if provider has required configuration (API keys, etc.)
     */
    public function isConfigured(): bool;

    /**
     * Fetch top headlines from the news provider
     * 
     * @param array $params Query parameters for filtering results
     * 
     * @return Collection<int,Article> Collection of Article DTOs
     */
    public function topHeadlines(array $params = []): Collection;

    /**
     * Search for articles across all available content
     * 
     * @param array $params Query parameters for searching
     * 
     * @return Collection<int,Article> Collection of Article DTOs
     */
    public function searchArticles(array $params = []): Collection;

    /**
     * Get the unique identifier key for this provider
     * 
     * @return string Provider key (e.g., 'newsapi', 'guardian', 'nyt')
     */
    public static function key(): string;
}
