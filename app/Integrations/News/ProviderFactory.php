<?php

namespace App\Integrations\News;

use App\Integrations\News\Contracts\NewsProvider;
use App\Integrations\News\Providers\GuardianProvider;
use App\Integrations\News\Providers\NewsApiProvider;
use App\Integrations\News\Providers\NytProvider;
use App\Services\SourceService;
use InvalidArgumentException;

/**
 * News Provider Factory
 *
 * Factory class responsible for creating instances of news providers
 * based on their unique identifier keys. Implements the Factory pattern
 * to centralize provider instantiation and ensure type safety.
 *
 * @package App\Integrations\News
 */
class ProviderFactory
{
    /**
     * Create a news provider instance by its key
     *
     * Supported providers:
     * - 'newsapi': NewsAPI.org integration
     * - 'guardian': The Guardian API integration
     * - 'nyt': New York Times API integration
     *
     * @param string $providerKey The unique identifier for the provider
     * @return NewsProvider Instance of the requested provider
     * @throws InvalidArgumentException When the provider key is not recognized
     *
     * @example
     * $guardian = ProviderFactory::make('guardian');
     * $articles = $guardian->topHeadlines(['category' => 'technology']);
     */
    public static function make(string $providerKey): NewsProvider
    {
        return match ($providerKey) {
            NewsApiProvider::key()  => new NewsApiProvider(app(SourceService::class)),
            GuardianProvider::key() => new GuardianProvider(),
            NytProvider::key()      => new NytProvider(),
            default => throw new InvalidArgumentException("Unknown provider [$providerKey]"),
        };
    }
}
