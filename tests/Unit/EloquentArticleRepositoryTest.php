<?php

namespace Tests\Unit;

use App\Repositories\EloquentArticleRepository;
use App\Models\Article;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Builder;
use Tests\TestCase;

/**
 * Eloquent Article Repository Unit Tests
 *
 * Tests the repository pattern implementation for Article data access
 * including filtering, pagination, and database operations.
 */
class EloquentArticleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected EloquentArticleRepository $repository;
    protected Source $testSource;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentArticleRepository();
        $this->testSource = Source::factory()->create(['is_active' => true]);
    }

    /**
     * Test search returns paginated results
     */
    public function test_search_returns_paginated_results(): void
    {
        // Clear any existing data
        Article::truncate();

        // Arrange
        Article::factory(25)->create(['source_id' => $this->testSource->id]);

        // Act
        $result = $this->repository->search([], 1, 10);

        // Assert
        $this->assertEquals(10, $result->count());
        $this->assertEquals(25, $result->total());
        $this->assertEquals(3, $result->lastPage());
    }

    /**
     * Test search filters by category
     */
    public function test_search_filters_by_category(): void
    {
        // Clear any existing data
        Article::truncate();

        // Arrange
        Article::factory(3)->create([
            'source_id' => $this->testSource->id,
            'category' => 'technology'
        ]);
        Article::factory(2)->create([
            'source_id' => $this->testSource->id,
            'category' => 'sports'
        ]);

        $filters = ['category' => 'technology'];

        // Act
        $result = $this->repository->search($filters, 1, 15);

        // Assert
        $this->assertEquals(3, $result->total());
        foreach ($result->items() as $article) {
            $this->assertEquals('technology', $article->category);
        }
    }

    /**
     * Test search searches by keyword
     */
    public function test_search_searches_by_keyword(): void
    {
        // Arrange
        Article::factory()->create([
            'source_id' => $this->testSource->id,
            'title' => 'Technology breakthrough in AI',
            'description' => 'Machine learning advances'
        ]);
        Article::factory()->create([
            'source_id' => $this->testSource->id,
            'title' => 'Sports update',
            'description' => 'Technology used in sports analytics'
        ]);
        Article::factory()->create([
            'source_id' => $this->testSource->id,
            'title' => 'Weather forecast',
            'description' => 'Sunny skies ahead'
        ]);

        $filters = ['keyword' => 'technology'];

        // Act
        $result = $this->repository->search($filters, 1, 15);

        // Assert
        $this->assertEquals(2, $result->total());
    }

    /**
     * Test search filters by date range
     */
    public function test_search_filters_by_date_range(): void
    {
        // Arrange
        $fromDate = now()->subDays(7);
        $toDate = now()->subDays(1);

        Article::factory()->create([
            'source_id' => $this->testSource->id,
            'published_at' => $fromDate->copy()->addDays(2)
        ]);
        Article::factory()->create([
            'source_id' => $this->testSource->id,
            'published_at' => $fromDate->copy()->subDays(1) // Before range
        ]);
        Article::factory()->create([
            'source_id' => $this->testSource->id,
            'published_at' => $toDate->copy()->addDays(1) // After range
        ]);

        $filters = [
            'from' => $fromDate->format('Y-m-d'),
            'to' => $toDate->format('Y-m-d')
        ];

        // Act
        $result = $this->repository->search($filters, 1, 15);

        // Assert
        $this->assertEquals(1, $result->total());
    }

    /**
     * Test findById returns article when found
     */
    public function test_find_by_id_returns_article_when_found(): void
    {
        // Arrange
        $article = Article::factory()->create(['source_id' => $this->testSource->id]);

        // Act
        $result = $this->repository->findById($article->id);

        // Assert
        $this->assertInstanceOf(Article::class, $result);
        $this->assertEquals($article->id, $result->id);
        $this->assertEquals($article->title, $result->title);
    }

    /**
     * Test findById returns null when not found
     */
    public function test_find_by_id_returns_null_when_not_found(): void
    {
        // Act
        $result = $this->repository->findById(999);

        // Assert
        $this->assertNull($result);
    }

    /**
     * Test deleteById removes article when found
     */
    public function test_delete_by_id_removes_article_when_found(): void
    {
        // Arrange
        $article = Article::factory()->create(['source_id' => $this->testSource->id]);

        // Act
        $result = $this->repository->deleteById($article->id);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }

    /**
     * Test search orders by published_at descending by default
     */
    public function test_search_orders_by_published_at_descending(): void
    {
        // Clear any existing data
        Article::truncate();

        // Arrange
        $oldest = Article::factory()->create([
            'source_id' => $this->testSource->id,
            'published_at' => now()->subDays(3)
        ]);
        $newest = Article::factory()->create([
            'source_id' => $this->testSource->id,
            'published_at' => now()->subDays(1)
        ]);
        $middle = Article::factory()->create([
            'source_id' => $this->testSource->id,
            'published_at' => now()->subDays(2)
        ]);

        // Act
        $result = $this->repository->search([], 1, 15);

        // Assert
        $articles = $result->items();
        $this->assertEquals($newest->id, $articles[0]->id);
        $this->assertEquals($middle->id, $articles[1]->id);
        $this->assertEquals($oldest->id, $articles[2]->id);
    }

    /**
     * Test search with empty filters returns all articles
     */
    public function test_search_with_empty_filters_returns_all_articles(): void
    {
        // Clear any existing data
        Article::truncate();

        // Arrange
        Article::factory(5)->create(['source_id' => $this->testSource->id]);

        // Act
        $result = $this->repository->search([], 1, 15);

        // Assert
        $this->assertEquals(5, $result->total());
    }

    /**
     * Test deleteById returns false when article not found
     */
    public function test_delete_by_id_returns_false_when_article_not_found(): void
    {
        // Act
        $result = $this->repository->deleteById(999);

        // Assert
        $this->assertFalse($result);
        $this->assertDatabaseCount('articles', 0);
    }
}
