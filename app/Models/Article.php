<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'title',
        'description',
        'url',
        'url_sha1',
        'image_url',
        'author',
        'publisher',
        'published_at',
        'provider',
        'category',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function sources() { return $this->hasMany(ArticleSource::class); }
}
