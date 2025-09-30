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
     */
    public function findByName(string $name, string $provider = 'newsapi'): ?Source
    {
        return Source::where(function ($query) use ($name) {
                $query->where('name', 'ilike', $name)
                      ->orWhere('source_id', 'ilike', $name);
            })
            ->where('provider', $provider)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get all source names across providers
     */
    public function getAllSourceNames(): array
    {
        return Source::where('is_active', true)
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
     */
    public function updateOrCreate(array $attributes, array $values): Source
    {
        return Source::updateOrCreate($attributes, $values);
    }

    /**
     * Get total count of active sources
     */
    public function getActiveCount(): int
    {
        return Source::where('is_active', true)->count();
    }
}