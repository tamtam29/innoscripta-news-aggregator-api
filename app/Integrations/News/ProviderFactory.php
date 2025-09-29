<?php

namespace App\Integrations\News;

use App\Integrations\News\Contracts\NewsProvider;
use App\Integrations\News\Providers\NewsApiProvider;
use InvalidArgumentException;

class ProviderFactory
{
    public static function make(string $providerKey): NewsProvider
    {
        return match ($providerKey) {
            NewsApiProvider::key()  => new NewsApiProvider(),
            default => throw new InvalidArgumentException("Unknown provider [$providerKey]"),
        };
    }
}
