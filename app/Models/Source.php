<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Source Model
 * 
 * Represents news sources from various providers (NewsAPI, Guardian, NYT).
 * Stores source metadata including name, description, URL, category, and provider information.
 * 
 * @package App\Models
 */
class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_id',
        'name',
        'description',
        'url',
        'category',
        'language',
        'country',
        'provider',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope to get only active sources
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}