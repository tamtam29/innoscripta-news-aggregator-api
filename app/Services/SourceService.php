<?php

namespace App\Services;

use App\Repositories\EloquentSourceRepository;

/**
 * Source Service
 * 
 * Handles source mapping and management operations
 */
class SourceService
{
    public function __construct(
        private EloquentSourceRepository $sourceRepository
    ) {}

    /**
     * Convert source name to NewsAPI source ID
     */
    public function getSourceIdByName(string $sourceName): ?string
    {
        $source = $this->sourceRepository->findByName($sourceName, 'newsapi');
        return $source?->source_id;
    }

    /**
     * Get all available source names across all providers
     */
    public function getAllSourceNames(): array
    {
        return $this->sourceRepository->getAllSourceNames();
    }

    /**
     * Get total count of active sources
     */
    public function getActiveSourcesCount(): int
    {
        return $this->sourceRepository->getActiveCount();
    }

    /**
     * Create or update source
     */
    public function updateOrCreateSource(array $attributes, array $values): \App\Models\Source
    {
        return $this->sourceRepository->updateOrCreate($attributes, $values);
    }
}