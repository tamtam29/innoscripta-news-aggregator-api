<?php

namespace App\Integrations\News\Supports;

/**
 * Taxonomy Support Class
 * 
 * Provides category normalization and mapping functionality to standardize
 * news categories across different providers. Each provider may use different
 * category names, and this class maps them to canonical internal categories.
 * 
 * @package App\Integrations\News\Supports
 */
class Taxonomy
{
    /**
     * Normalize a category value to canonical form
     * 
     * Converts provider-specific category names to standardized internal
     * category keys. Handles case-insensitive matching and provider-specific
     * aliases defined in the taxonomy configuration.
     * 
     * Process:
     * 1. Check if value is already a canonical category key
     * 2. Check provider-specific aliases (if provider specified)
     * 3. Check category labels for exact match
     * 4. Return null if no match found
     * 
     * @param string|null $val The category value to normalize
     * @param string|null $provider Provider key for alias lookup
     * @return string|null Canonical category key or null if not found
     * 
     * @example
     * // Direct canonical key
     * Taxonomy::canonicalizeCategory('technology'); // 'technology'
     * 
     * // Provider-specific alias
     * Taxonomy::canonicalizeCategory('tech', 'newsapi'); // 'technology'
     * 
     * // Label matching
     * Taxonomy::canonicalizeCategory('Technology'); // 'technology'
     */
    public static function canonicalizeCategory(?string $val, ?string $provider = null): ?string
    {
        if (!$val) return null;
        $val = trim(mb_strtolower($val));

        $categories = array_keys(config('taxonomy.categories', []));
        if (in_array($val, $categories, true)) return $val;

        if ($provider) {
            foreach (config("taxonomy.aliases.$provider", []) as $from => $to) {
                if (trim(mb_strtolower($from)) === $val) return $to;
            }
        }

        foreach (config('taxonomy.categories', []) as $key => $label) {
            if (mb_strtolower($label) === $val) return $key;
        }

        return null;
    }

    /**
     * Normalize multiple category values
     * 
     * Processes an array or single string of category values and returns
     * an array of unique canonical category keys. Filters out duplicates
     * and null values.
     * 
     * @param string|array|null $values Category value(s) to normalize
     * @param string|null $provider Provider key for alias lookup
     * @return array Array of unique canonical category keys
     * 
     * @example
     * Taxonomy::canonicalizeMany(['tech', 'business', 'invalid']);
     * // Returns: ['technology', 'business']
     * 
     * Taxonomy::canonicalizeMany('technology, sports', 'newsapi');
     * // Returns: ['technology', 'sports']
     */
    public static function canonicalizeMany(string|array|null $values, ?string $provider = null): array
    {
        if (!$values) return [];
        $values = is_array($values) ? $values : [$values];
        $out = [];
        foreach ($values as $v) {
            $key = self::canonicalizeCategory($v, $provider);
            if ($key) $out[$key] = true;
        }
        return array_keys($out);
    }
}
