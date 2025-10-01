<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Preference Model
 *
 * Represents user preferences for news filtering including
 * sources, categories, and authors.
 *
 * @package App\Models
 */
class Preference extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'category',
        'author',
    ];

    /**
     * Check if any preferences are set
     *
     * @return bool
     */
    public function hasPreference(): bool
    {
        return !empty($this->sources) ||
               !empty($this->categories) ||
               !empty($this->authors);
    }
}
