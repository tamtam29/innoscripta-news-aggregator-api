<?php

namespace Tests\Unit;

use App\Integrations\News\Providers\NewsApiProvider;
use App\Integrations\News\DTOs\Article;
use App\Services\SourceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * NewsAPI Provider Unit Tests
 *
 * Tests the NewsApiProvider integration including API calls,
 * rate limiting, data transformation, and error handling.
 */
class NewsApiProviderTest extends TestCase
{
    protected NewsApiProvider $provider;
    protected $mockSourceService;

    /**
     * Set up test environment with mocked dependencies
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSourceService = Mockery::mock(SourceService::class);

        // Mock configuration
        config(['news.newsapi.key' => 'test-api-key']);
        config(['news.newsapi.base' => 'https://newsapi.org/v2/']);

        $this->provider = new NewsApiProvider($this->mockSourceService);
    }

    /**
     * Clean up mocks after each test
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test provider is configured when API key exists
     */
    public function test_provider_is_configured_when_api_key_exists(): void
    {
        // Act & Assert
        $this->assertTrue($this->provider->isConfigured());
    }

    /**
     * Test provider is not configured when API key is missing
     */
    public function test_provider_is_not_configured_when_api_key_missing(): void
    {
        // Arrange
        config(['news.newsapi.key' => null]);
        $provider = new NewsApiProvider($this->mockSourceService);

        // Act & Assert
        $this->assertFalse($provider->isConfigured());
    }

    /**
     * Test provider key returns correct identifier
     */
    public function test_provider_key_returns_correct_identifier(): void
    {
        // Act & Assert
        $this->assertEquals('newsapi', NewsApiProvider::key());
    }

    /**
     * Test topHeadlines fetches articles successfully
     */
    public function test_top_headlines_fetches_articles_successfully(): void
    {
        // Arrange
        $mockResponse = [
            'status' => 'ok',
            'articles' => [
                [
                    'title' => 'Test Article',
                    'description' => 'Test description',
                    'url' => 'https://example.com/article',
                    'urlToImage' => 'https://example.com/image.jpg',
                    'author' => 'John Doe',
                    'source' => ['name' => 'Test Source'],
                    'publishedAt' => '2025-10-01T12:00:00Z'
                ]
            ]
        ];

        Http::fake([
            'newsapi.org/v2/top-headlines*' => Http::response($mockResponse, 200)
        ]);

        // Act
        $result = $this->provider->topHeadlines(['category' => 'technology']);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);

        $article = $result->first();
        $this->assertInstanceOf(Article::class, $article);
        $this->assertEquals('Test Article', $article->title);
        $this->assertEquals('Test description', $article->description);
        $this->assertEquals('https://example.com/article', $article->url);
    }

    /**
     * Test searchArticles with keyword parameter
     */
    public function test_search_articles_with_keyword(): void
    {
        // Arrange
        $mockResponse = [
            'status' => 'ok',
            'articles' => [
                [
                    'title' => 'AI Technology News',
                    'description' => 'Latest AI developments',
                    'url' => 'https://example.com/ai-news',
                    'urlToImage' => null,
                    'author' => 'Tech Reporter',
                    'source' => ['name' => 'Tech News'],
                    'publishedAt' => '2025-10-01T15:30:00Z'
                ]
            ]
        ];

        Http::fake([
            'newsapi.org/v2/everything*' => Http::response($mockResponse, 200)
        ]);

        // Act
        $result = $this->provider->searchArticles(['keyword' => 'AI technology']);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);

        $article = $result->first();
        $this->assertEquals('AI Technology News', $article->title);
        $this->assertEquals('Latest AI developments', $article->description);
    }

    /**
     * Test provider handles API errors gracefully
     */
    public function test_provider_handles_api_errors_gracefully(): void
    {
        // Arrange
        Log::shouldReceive('error')->once();

        Http::fake([
            'newsapi.org/v2/top-headlines*' => Http::response(['error' => 'API key invalid'], 401)
        ]);

        // Act
        $result = $this->provider->topHeadlines([]);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    /**
     * Test buildTopHeadlinesParams creates correct parameters
     */
    public function test_build_top_headlines_params_creates_correct_parameters(): void
    {
        // Arrange
        $this->mockSourceService
            ->shouldReceive('getSourceIdByName')
            ->with('BBC News')
            ->andReturn('bbc-news');

        $params = [
            'source' => 'BBC News',
            'category' => 'technology',
            'pageSize' => 100
        ];

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('buildTopHeadlinesParams');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->provider, $params);

        // Assert
        $expected = [
            'apiKey' => 'test-api-key',
            'sources' => 'bbc-news',
            'category' => 'technology',
            'pageSize' => 100,
            'page' => 1,
            'sortBy' => 'popularity'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test buildEverythingParams creates correct parameters
     */
    public function test_build_everything_params_creates_correct_parameters(): void
    {
        // Arrange
        $params = [
            'keyword' => 'climate change',
            'from' => '2025-10-01',
            'to' => '2025-10-31',
            'pageSize' => 100
        ];

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('buildEverythingParams');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->provider, $params);

        // Assert
        $expected = [
            'apiKey' => 'test-api-key',
            'q' => 'climate change',
            'from' => '2025-10-01',
            'to' => '2025-10-31',
            'language' => 'en',
            'sortBy' => 'publishedAt',
            'pageSize' => 100,
            'page' => 1
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test createArticle transforms API data correctly
     */
    public function test_create_article_transforms_api_data_correctly(): void
    {
        // Arrange
        $apiData = [
            'title' => 'Breaking News Title',
            'description' => 'News description',
            'url' => 'https://example.com/news',
            'urlToImage' => 'https://example.com/image.jpg',
            'author' => 'Jane Reporter',
            'source' => ['name' => 'News Source'],
            'publishedAt' => '2025-10-01T18:45:00Z'
        ];

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('createArticle');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->provider, $apiData);

        // Assert
        $this->assertInstanceOf(Article::class, $result);
        $this->assertEquals('Breaking News Title', $result->title);
        $this->assertEquals('News description', $result->description);
        $this->assertEquals('https://example.com/news', $result->url);
        $this->assertEquals('https://example.com/image.jpg', $result->imageUrl);
        $this->assertEquals('Jane Reporter', $result->author);
        $this->assertEquals('News Source', $result->source);
        $this->assertEquals('newsapi', $result->provider);
    }

    /**
     * Test createArticle handles missing data gracefully
     */
    public function test_create_article_handles_missing_data_gracefully(): void
    {
        // Arrange
        $apiData = [
            'title' => 'Minimal Article',
            'url' => 'https://example.com/minimal',
            'source' => ['name' => 'Source'],
            'publishedAt' => '2025-10-01T12:00:00Z'
        ];

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('createArticle');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->provider, $apiData);

        // Assert
        $this->assertInstanceOf(Article::class, $result);
        $this->assertEquals('Minimal Article', $result->title);
        $this->assertNull($result->description);
        $this->assertNull($result->imageUrl);
        $this->assertNull($result->author);
        $this->assertEquals('Source', $result->source);
    }

    /**
     * Test provider returns empty collection when not configured
     */
    public function test_provider_returns_empty_collection_when_not_configured(): void
    {
        // Arrange
        config(['news.newsapi.key' => null]);
        $provider = new NewsApiProvider($this->mockSourceService);

        // Act
        $headlines = $provider->topHeadlines([]);
        $search = $provider->searchArticles([]);

        // Assert
        $this->assertInstanceOf(Collection::class, $headlines);
        $this->assertTrue($headlines->isEmpty());

        $this->assertInstanceOf(Collection::class, $search);
        $this->assertTrue($search->isEmpty());
    }
}
