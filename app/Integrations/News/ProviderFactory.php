<?php

namespace App\Integrations\News;

use App\Integrations\News\Contracts\NewsProvider;
use App\Integrations\News\Providers\GuardianProvider;
use App\Integrations\News\Providers\NewsApiProvider;
use App\Integrations\News\Providers\NytProvider;
use InvalidArgumentException;

class ProviderFactory
{
    public static function make(string $providerKey): NewsProvider
    {
        return match ($providerKey) {
            NewsApiProvider::key()  => new NewsApiProvider(),
            GuardianProvider::key() => new GuardianProvider(),
            NytProvider::key()      => new NytProvider(),
            default => throw new InvalidArgumentException("Unknown provider [$providerKey]"),
        };
    }
}
