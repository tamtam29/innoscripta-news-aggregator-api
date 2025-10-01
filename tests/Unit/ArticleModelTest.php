<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Article Model Unit Tests
 *
 * Tests the Article model's relationships, scopes, and business logic
 * without external dependencies or HTTP requests.
 */
class ArticleModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test article belongs to source relationship
     */
    public function test_article_belongs_to_source(): void
    {
        // Arrange
        $source = Source::factory()->create();
        $article = Article::factory()->create(['source_id' => $source->id]);

        // Act & Assert
        $this->assertInstanceOf(Source::class, $article->source);
        $this->assertEquals($source->id, $article->source->id);
        $this->assertEquals($source->name, $article->source->name);
    }

    /**
     * Test article fillable attributes
     */
    public function test_article_has_correct_fillable_attributes(): void
    {
        // Arrange
        $expectedFillable = [
            'title',
            'description',
            'url',
            'url_sha1',
            'image_url',
            'author',
            'source_id',
            'provider',
            'published_at',
            'category',
        ];

        // Act
        $article = new Article();

        // Assert
        $this->assertEquals($expectedFillable, $article->getFillable());
    }

    /**
     * Test article casts attributes correctly
     */
    public function test_article_casts_attributes_correctly(): void
    {
        // Arrange
        $article = Article::factory()->create([
            'published_at' => '2025-10-01 12:00:00'
        ]);

        // Act & Assert
        $this->assertInstanceOf(\Carbon\Carbon::class, $article->published_at);
    }

    /**
     * Test category filtering with direct query
     */
    public function test_can_filter_by_category(): void
    {
        // Arrange
        $source = Source::factory()->create();
        $techArticle = Article::factory()->create([
            'source_id' => $source->id,
            'category' => 'technology'
        ]);
        $sportsArticle = Article::factory()->create([
            'source_id' => $source->id,
            'category' => 'sports'
        ]);

        // Act
        $techArticles = Article::where('category', 'technology')->get();

        // Assert
        $this->assertCount(1, $techArticles);
        $this->assertEquals($techArticle->id, $techArticles->first()->id);
        $this->assertEquals('technology', $techArticles->first()->category);
    }

    /**
     * Test source filtering with direct query
     */
    public function test_can_filter_by_source(): void
    {
        // Arrange
        $bbcSource = Source::factory()->create(['name' => 'BBC News']);
        $cnnSource = Source::factory()->create(['name' => 'CNN']);

        $bbcArticle = Article::factory()->create(['source_id' => $bbcSource->id]);
        $cnnArticle = Article::factory()->create(['source_id' => $cnnSource->id]);

        // Act
        $bbcArticles = Article::whereHas('source', function ($q) {
            $q->where('name', 'BBC News');
        })->get();

        // Assert
        $this->assertCount(1, $bbcArticles);
        $this->assertEquals($bbcArticle->id, $bbcArticles->first()->id);
    }

    /**
     * Test keyword search with direct query
     */
    public function test_can_search_by_keyword(): void
    {
        // Arrange
        $source = Source::factory()->create();
        $matchingTitle = Article::factory()->create([
            'source_id' => $source->id,
            'title' => 'Technology breakthrough',
            'description' => 'Sports news'
        ]);
        $matchingDescription = Article::factory()->create([
            'source_id' => $source->id,
            'title' => 'Sports update',
            'description' => 'New technology development'
        ]);
        $nonMatching = Article::factory()->create([
            'source_id' => $source->id,
            'title' => 'Weather forecast',
            'description' => 'Sunny skies ahead'
        ]);

        // Act
        $results = Article::where(function ($q) {
            $q->where('title', 'ILIKE', '%technology%')
              ->orWhere('description', 'ILIKE', '%technology%');
        })->get();

        // Assert
        $this->assertCount(2, $results);
        $this->assertTrue($results->contains($matchingTitle));
        $this->assertTrue($results->contains($matchingDescription));
        $this->assertFalse($results->contains($nonMatching));
    }

    /**
     * Test date range filtering with direct query
     */
    public function test_can_filter_by_date_range(): void
    {
        // Arrange
        $source = Source::factory()->create();
        $startDate = now()->subDays(7);
        $endDate = now()->subDays(1);

        $withinRange = Article::factory()->create([
            'source_id' => $source->id,
            'published_at' => $startDate->copy()->addDays(3)
        ]);
        $beforeRange = Article::factory()->create([
            'source_id' => $source->id,
            'published_at' => $startDate->copy()->subDays(1)
        ]);
        $afterRange = Article::factory()->create([
            'source_id' => $source->id,
            'published_at' => $endDate->copy()->addDays(1)
        ]);

        // Act
        $results = Article::whereBetween('published_at', [$startDate, $endDate])->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals($withinRange->id, $results->first()->id);
    }

    /**
     * Test ordering by published date with direct query
     */
    public function test_can_order_by_published_date(): void
    {
        // Arrange
        $source = Source::factory()->create();
        $oldest = Article::factory()->create([
            'source_id' => $source->id,
            'published_at' => now()->subDays(3)
        ]);
        $newest = Article::factory()->create([
            'source_id' => $source->id,
            'published_at' => now()->subDays(1)
        ]);
        $middle = Article::factory()->create([
            'source_id' => $source->id,
            'published_at' => now()->subDays(2)
        ]);

        // Act
        $results = Article::orderBy('published_at', 'desc')->get();

        // Assert
        $this->assertEquals($newest->id, $results->first()->id);
        $this->assertEquals($middle->id, $results->get(1)->id);
        $this->assertEquals($oldest->id, $results->last()->id);
    }

    /**
     * Test article creation with all attributes
     */
    public function test_can_create_article_with_all_attributes(): void
    {
        // Arrange
        $source = Source::factory()->create();
        $articleData = [
            'title' => 'Test Article Title',
            'description' => 'Test article description',
            'url' => 'https://example.com/article',
            'url_sha1' => sha1('https://example.com/article'),
            'image_url' => 'https://example.com/image.jpg',
            'author' => 'John Doe',
            'source_id' => $source->id,
            'published_at' => now(),
            'provider' => 'newsapi',
            'category' => 'technology',
        ];

        // Act
        $article = Article::create($articleData);

        // Assert
        $this->assertDatabaseHas('articles', [
            'title' => 'Test Article Title',
            'description' => 'Test article description',
            'url' => 'https://example.com/article',
            'url_sha1' => sha1('https://example.com/article'),
            'author' => 'John Doe',
            'provider' => 'newsapi',
            'category' => 'technology',
        ]);

        $this->assertInstanceOf(Article::class, $article);
        $this->assertEquals('Test Article Title', $article->title);
    }
}
