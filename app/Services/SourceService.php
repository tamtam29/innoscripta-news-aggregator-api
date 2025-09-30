<?php

namespace App\Services;

use App\Repositories\EloquentSourceRepository;

/**
 * Source Service
 *
 * Handles source mapping and management operations
 *
 * @package App\Services
 */
class SourceService
{
    public function __construct(
        private EloquentSourceRepository $sourceRepository
    ) {
    }

    /**
     * Convert source name to NewsAPI source ID
     *
     * @param string $sourceName Source name to convert
     * @return string|null
     */
    public function getSourceIdByName(string $sourceName): ?string
    {
        $source = $this->sourceRepository->findByName($sourceName, 'newsapi');
        return $source?->source_id;
    }

    /**
     * Get all available source names across all providers
     *
     * @return array Array of sources with source_id and source_name
     */
    public function getAllSourceNames(): array
    {
        return $this->sourceRepository->getAllSourceNames();
    }

    /**
     * Get total count of active sources
     *
     * @return int Number of active sources
     */
    public function getActiveSourcesCount(): int
    {
        return $this->sourceRepository->getActiveCount();
    }

    /**
     * Create or update source
     *
     * @param array $attributes Attributes to match for existing record
     * @param array $values Values to update or create with
     * @return \App\Models\Source
     */
    public function updateOrCreateSource(array $attributes, array $values): \App\Models\Source
    {
        return $this->sourceRepository->updateOrCreate($attributes, $values);
    }
}
