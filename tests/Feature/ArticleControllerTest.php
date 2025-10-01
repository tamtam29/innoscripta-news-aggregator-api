<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Article Controller Feature Tests
 *
 * Tests the complete HTTP flow for article management endpoints
 * without authentication requirements.
 */
class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * Test fetching headlines with default parameters
     */
    public function test_can_fetch_headlines_with_default_parameters(): void
    {
        // Arrange
        $source = Source::factory()->create(['is_active' => true]);
        Article::factory(5)->create(['source_id' => $source->id]);

        // Act
        $response = $this->getJson('/api/news/headlines');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'url',
                        'image_url',
                        'author',
                        'source',
                        'published_at',
                        'category'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total'
                ]
            ]);

        $meta = $response->json('meta');
        $this->assertEquals(20, $meta['per_page']);
        $this->assertEquals(5, $meta['total']);
        $this->assertEquals(1, $meta['last_page']);
    }

    /**
     * Test filtering articles by source
     */
    public function test_can_filter_articles_by_source(): void
    {
        // Arrange
        $targetSource = Source::factory()->create(['name' => 'BBC News', 'is_active' => true]);
        $otherSource = Source::factory()->create(['name' => 'CNN', 'is_active' => true]);

        Article::factory(3)->create(['source_id' => $targetSource->id]);
        Article::factory(2)->create(['source_id' => $otherSource->id]);

        // Act
        $response = $this->getJson('/api/news/headlines?source=BBC News');

        // Assert
        $response->assertOk();
        $articles = $response->json('data');

        $this->assertCount(3, $articles);
        foreach ($articles as $article) {
            $this->assertEquals('BBC News', $article['source']);
        }
    }

    /**
     * Test filtering articles by category
     */
    public function test_can_filter_articles_by_category(): void
    {
        // Arrange
        $source = Source::factory()->create(['is_active' => true]);
        Article::factory(2)->create(['source_id' => $source->id, 'category' => 'technology']);
        Article::factory(3)->create(['source_id' => $source->id, 'category' => 'sports']);

        // Act
        $response = $this->getJson('/api/news/headlines?category=technology');

        // Assert
        $response->assertOk();
        $articles = $response->json('data');

        $this->assertCount(2, $articles);
        foreach ($articles as $article) {
            $this->assertEquals('technology', $article['category']);
        }
    }

    /**
     * Test searching articles by keyword
     */
    public function test_can_search_articles_by_keyword(): void
    {
        // Arrange
        $source = Source::factory()->create(['is_active' => true]);
        Article::factory()->create([
            'source_id' => $source->id,
            'title' => 'Breaking news about technology',
            'description' => 'Latest tech innovations'
        ]);
        Article::factory()->create([
            'source_id' => $source->id,
            'title' => 'Sports update',
            'description' => 'Football match results'
        ]);

        // Act
        $response = $this->getJson('/api/news/search?keyword=technology');

        // Assert
        $response->assertOk();
        $articles = $response->json('data');

        $this->assertCount(1, $articles);
        $this->assertStringContainsString('technology', strtolower($articles[0]['title']));
    }

    /**
     * Test date range filtering
     */
    public function test_can_filter_articles_by_date_range(): void
    {
        // Arrange
        $source = Source::factory()->create(['is_active' => true]);
        $fromDate = now()->subDays(7);
        $toDate = now()->subDays(1);

        Article::factory()->create([
            'source_id' => $source->id,
            'published_at' => $fromDate->addDays(2)
        ]);
        Article::factory()->create([
            'source_id' => $source->id,
            'published_at' => now()->subDays(10) // Outside range
        ]);

        // Act
        $response = $this->getJson('/api/news/headlines?from=' . $fromDate->format('Y-m-d') . '&to=' . $toDate->format('Y-m-d'));

        // Assert
        $response->assertOk();
        $articles = $response->json('data');

        $this->assertCount(1, $articles);
    }
}
