<?php

namespace App\Integrations\News;

use Illuminate\Support\Collection;

class ProviderAggregator
{
    /** @return Collection<int,\App\News\Contracts\NewsProvider> */
    public function enabled(): Collection
    {
        return collect(config('news.enabled_providers', []))
            ->map(fn($key) => ProviderFactory::make($key));
    }
}
