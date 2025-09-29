<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function article() { return $this->belongsTo(Article::class); }

}
