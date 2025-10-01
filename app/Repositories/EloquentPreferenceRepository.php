<?php

namespace App\Repositories;

use App\Models\Preference;
use App\Repositories\Contracts\PreferenceRepository;

/**
 * Eloquent Preference Repository
 *
 * Implements preference data access operations using Eloquent ORM.
 * Handles single preference record for the application since no user auth.
 * Following the same pattern as EloquentArticleRepository.
 *
 * @package App\Repositories
 */
class EloquentPreferenceRepository implements PreferenceRepository
{
    /**
     * Get the current preference record (singleton approach)
     *
     * @return Preference|null
     */
    public function getPreference(): ?Preference
    {
        return Preference::first();
    }

    /**
     * Create or update preferences
     * Since no user auth, we maintain a single preference record
     *
     * @param array $data
     * @return Preference
     */
    public function savePreference(array $data): Preference
    {
        $preference = $this->getPreference();

        if ($preference) {
            $preference->update($data);
            return $preference;
        }

        return Preference::create($data);
    }
}
