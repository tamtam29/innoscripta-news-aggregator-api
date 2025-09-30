<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Article Model
 *
 * @package App\Models
 *
 * @OA\Schema(
 *     schema="Article",
 *     title="Article",
 *     description="News article entity",
 *     @OA\Property(property="id", type="integer", example=1, description="Unique identifier for the article"),
 *     @OA\Property(property="title", type="string", example="Breaking: Major Technology Advancement Announced", description="Article headline/title"),
 *     @OA\Property(property="description", type="string", example="A groundbreaking technological innovation has been unveiled today, promising to revolutionize the industry.", description="Brief description or excerpt of the article"),
 *     @OA\Property(property="url", type="string", format="url", example="https://example.com/article/tech-advancement", description="Direct URL to the full article"),
 *     @OA\Property(property="image_url", type="string", format="url", nullable=true, example="https://example.com/images/tech-article.jpg", description="URL to the article's featured image"),
 *     @OA\Property(property="author", type="string", nullable=true, example="John Smith", description="Article author name"),
 *     @OA\Property(property="source", type="string", nullable=true, example="TechCrunch", description="Publishing organization or website"),
 *     @OA\Property(property="published_at", type="string", format="date-time", example="2025-09-30T10:30:00Z", description="Article publication date and time"),
 *     @OA\Property(property="provider", type="string", enum={"newsapi", "guardian", "nyt"}, example="newsapi", description="News provider source"),
 *     @OA\Property(property="category", type="string", nullable=true, example="technology", description="Article category/topic"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-30T10:30:00Z", description="Record creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-30T10:30:00Z", description="Record last update timestamp")
 * )
 */
class Article extends Model
{
    protected $fillable = [
        'title',
        'description',
        'url',
        'url_sha1',
        'image_url',
        'author',
        'source',
        'published_at',
        'provider',
        'category',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Get the article sources for this article
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function article_sources()
    {
        return $this->hasMany(ArticleSource::class);
    }

    /**
     * Get the source that this article belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function source()
    {
        return $this->belongsTo(Source::class);
    }
}
