<?php

namespace App\Repositories;

use App\Models\Source;
use App\Repositories\Contracts\SourceRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent Source Repository
 *
 * Handles source data access using Eloquent ORM
 */
class EloquentSourceRepository implements SourceRepository
{
    /**
     * Get source by display name
     *
     * @param string $name Source name to search for
     * @param string $provider Provider name (default: 'newsapi')
     * @return Source|null
     */
    public function findByName(string $name, string $provider = 'newsapi'): ?Source
    {
        return Source::where(function ($query) use ($name) {
            $query->where('name', 'ilike', $name)
                  ->orWhere('source_id', 'ilike', $name);
        })
            ->where('provider', $provider)
            ->active()
            ->first();
    }

    /**
     * Get all source names across providers
     *
     * @return array Array of sources with source_id and source_name
     */
    public function getAllSourceNames(): array
    {
        return Source::active()
            ->get(['source_id', 'name'])
            ->map(function ($source) {
                return [
                    'source_id' => $source->source_id,
                    'source_name' => $source->name,
                ];
            })
            ->toArray();
    }

    /**
     * Create or update source
     *
     * @param array $attributes Attributes to match for existing record
     * @param array $values Values to update or create with
     * @return Source
     */
    public function updateOrCreate(array $attributes, array $values): Source
    {
        return Source::updateOrCreate($attributes, $values);
    }

    /**
     * Get total count of active sources
     *
     * @return int Number of active sources
     */
    public function getActiveCount(): int
    {
        return Source::active()->count();
    }
}
