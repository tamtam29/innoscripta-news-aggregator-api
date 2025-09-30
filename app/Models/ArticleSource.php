<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ArticleSource Model
 *
 * Represents the relationship between articles and their external provider sources.
 * Stores provider-specific metadata and external identifiers for tracking.
 *
 * @package App\Models
 */
class ArticleSource extends Model
{
    protected $fillable = [
        'article_id',
        'provider',
        'external_id',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    /**
     * Get the article that this source record belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
