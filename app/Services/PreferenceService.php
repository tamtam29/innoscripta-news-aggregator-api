<?php

namespace App\Services;

use App\Models\Preference;
use App\Repositories\EloquentPreferenceRepository;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Preference Service
 *
 * Business logic layer for managing user preferences.
 * Handles validation, formatting, and coordination between
 * repository and controller layers.
 * Following the same pattern as NewsService.
 *
 * @package App\Services
 */
class PreferenceService
{
    /**
     * @var PreferenceRepository
     */
    private EloquentPreferenceRepository $preferenceRepository;

    public function __construct(EloquentPreferenceRepository $preferenceRepository)
    {
        $this->preferenceRepository = $preferenceRepository;
    }

    /**
     * Get all current preferences
     *
     * @return Preference
     */
    public function getPreference(): Preference
    {
        return $this->preferenceRepository->getPreference();
    }

    /**
     * Update preferences with validation
     *
     * @param array $data
     * @return Preference
     * @throws ValidationException
     */
    public function updatePreferences(array $data): Preference
    {
        return $this->preferenceRepository->savePreference($data);
    }

    /**
     * Check if any preferences are currently set
     *
     * @return bool
     */
    public function hasAnyPreferences(): bool
    {
        $preference = $this->preferenceRepository->getPreference();
        return $preference?->hasPreference() ?? false;
    }
}
