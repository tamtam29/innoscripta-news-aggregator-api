<?php

namespace App\Integrations\News\Supports;

class Taxonomy
{
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
