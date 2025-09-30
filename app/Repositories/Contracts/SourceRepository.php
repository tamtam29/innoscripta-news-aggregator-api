<?php

namespace App\Repositories\Contracts;

use App\Models\Source;
use Illuminate\Database\Eloquent\Collection;

/**
 * Source Repository Contract
 * 
 * Defines the interface for source data access
 */
interface SourceRepository
{
    /**
     * Get source by display name
     * 
     * @param string $name The display name of the source
     * @param string $provider The provider name (newsapi, guardian, nyt)
     * @return Source|null
     */
    public function findByName(string $name, string $provider = 'newsapi'): ?Source;

    /**
     * Get all source names across providers
     * 
     * @return array<string> Array of unique source names
     */
    public function getAllSourceNames(): array;

    /**
     * Create or update source
     * 
     * @param array $attributes Source attributes
     * @param array $values Values to update
     * @return Source
     */
    public function updateOrCreate(array $attributes, array $values): Source;

    /**
     * Get total count of active sources
     * 
     * @return int
     */
    public function getActiveCount(): int;
}