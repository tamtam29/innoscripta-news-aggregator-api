<?php

namespace App\Integrations\News;

use App\Integrations\News\Contracts\NewsProvider;
use Illuminate\Support\Collection;

/**
 * News Provider Aggregator
 *
 * Manages multiple news providers and provides a unified interface
 * for working with all enabled providers simultaneously. Handles
 * provider configuration and instantiation based on application settings.
 *
 * @package App\Integrations\News
 */
class ProviderAggregator
{
    /**
     * Get all enabled news providers
     *
     * Reads the enabled providers from configuration and returns
     * instantiated provider objects ready for use.
     *
     * Configuration is read from: config('news.enabled_providers')
     *
     * @return Collection<int,NewsProvider> Collection of enabled provider instances
     *
     * @example
     * $aggregator = new ProviderAggregator();
     * $providers = $aggregator->enabled();
     *
     * foreach ($providers as $provider) {
     *     $articles = $provider->topHeadlines(['category' => 'technology']);
     *     // Process articles...
     * }
     */
    public function enabled(): Collection
    {
        return collect(config('news.enabled_providers', []))
            ->map(fn ($key) => ProviderFactory::make($key));
    }
}
