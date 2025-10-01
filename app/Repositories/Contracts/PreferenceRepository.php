<?php

namespace App\Repositories\Contracts;

use App\Models\Preference;

/**
 * Preference Repository Contract
 *
 * Defines the interface for preference data operations.
 * Following the same pattern as ArticleRepository contract.
 *
 * @package App\Repositories\Contracts
 */
interface PreferenceRepository
{
    /**
     * Get the current preference record
     *
     * @return Preference|null
     */
    public function getPreference(): ?Preference;

    /**
     * Create or update preferences
     *
     * @param array $data
     * @return Preference
     */
    public function savePreference(array $data): Preference;
}
