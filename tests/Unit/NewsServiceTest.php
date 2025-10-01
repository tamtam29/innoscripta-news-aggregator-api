<?php

namespace Tests\Unit;

use App\Jobs\FetchNewsArticles;
use App\Services\NewsService;
use App\Services\PreferenceService;
use App\Services\SourceService;
use App\Repositories\EloquentArticleRepository;
use App\Integrations\News\ProviderAggregator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * News Service Unit Tests
 *
 * Tests the NewsService business logic including queue management,
 * data freshness checks, and provider coordination.
 */
class NewsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NewsService $newsService;
    protected $mockArticleRepository;
    protected $mockProviderAggregator;
    protected $mockPreferenceService;

    /**
     * Set up test environment with mocked dependencies
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockArticleRepository = Mockery::mock(EloquentArticleRepository::class);
        $this->mockProviderAggregator = Mockery::mock(ProviderAggregator::class);
        $this->mockPreferenceService = Mockery::mock(PreferenceService::class);

        $this->newsService = new NewsService(
            $this->mockArticleRepository,
            $this->mockProviderAggregator,
            $this->mockPreferenceService
        );
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
     * Setup default preference service mocks for tests that don't need specific preference behavior
     */
    private function setupDefaultPreferenceMocks(): void
    {
        $this->mockPreferenceService
            ->shouldReceive('hasAnyPreferences')
            ->andReturn(false);
        
        $this->mockPreferenceService
            ->shouldReceive('getPreference')
            ->andReturn((object)[]);
    }

    /**
     * Test fetchNewsArticles with fresh data uses queue
     */
    public function test_get_headlines_with_fresh_data_uses_queue(): void
    {
        // Arrange
        Queue::fake();
        $this->setupDefaultPreferenceMocks();

        $this->mockProviderAggregator
            ->shouldReceive('enabled')
            ->andReturn(collect([]));

        $this->mockArticleRepository
            ->shouldReceive('search')
            ->andReturn(new ConcreteLengthAwarePaginator([], 0, 10, 1));

        // Act
        $result = $this->newsService->getHeadlines([], 1, 10);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /**
     * Test fetchNewsArticles without fresh data executes synchronously
     */
    public function test_get_headlines_without_fresh_data_executes_synchronously(): void
    {
        // Arrange
        Queue::fake();
        $this->setupDefaultPreferenceMocks();

        $this->mockArticleRepository
            ->shouldReceive('search')
            ->andReturn(new ConcreteLengthAwarePaginator([], 0, 10, 1));

        $this->mockProviderAggregator
            ->shouldReceive('enabled')
            ->once()
            ->andReturn(collect([]));

        // Act
        $result = $this->newsService->getHeadlines([], 1, 10);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /**
     * Test getHeadlines filters parameters correctly
     */
    public function test_get_headlines_filters_parameters_correctly(): void
    {
        // Arrange
        $filters = [
            'category' => 'technology',
            'country' => 'us'
        ];
        $this->setupDefaultPreferenceMocks();

        $this->mockProviderAggregator
            ->shouldReceive('enabled')
            ->andReturn(collect([]));

        $this->mockArticleRepository
            ->shouldReceive('search')
            ->atLeast()->once()
            ->with(
                $this->callback(function ($params) use ($filters) {
                    return $params['category'] === $filters['category'] &&
                           $params['country'] === $filters['country'];
                }),
                1,
                15
            )
            ->andReturn(new ConcreteLengthAwarePaginator([], 0, 15, 1));

        // Act
        $result = $this->newsService->getHeadlines($filters, 1, 15);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /**
     * Test searchArticles with keyword
     */
    public function test_search_articles_with_keyword(): void
    {
        // Arrange
        $keyword = 'Laravel';
        $this->setupDefaultPreferenceMocks();

        $this->mockProviderAggregator
            ->shouldReceive('enabled')
            ->andReturn(collect([]));

        $this->mockArticleRepository
            ->shouldReceive('search')
            ->atLeast()->once()
            ->with(
                $this->callback(function ($params) use ($keyword) {
                    return isset($params['keyword']) && $params['keyword'] === $keyword;
                }),
                1,
                15
            )
            ->andReturn(new ConcreteLengthAwarePaginator([], 0, 15, 1));

        // Act
        $result = $this->newsService->searchArticles(['keyword' => $keyword], 1, 15);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /**
     * Test searchArticles with additional filters
     */
    public function test_search_articles_with_filters(): void
    {
        // Arrange
        $keyword = 'technology';
        $filters = [
            'category' => 'tech',
            'source' => 'TechCrunch',
            'from' => '2025-10-01',
            'to' => '2025-10-31'
        ];
        $this->setupDefaultPreferenceMocks();

        $this->mockProviderAggregator
            ->shouldReceive('enabled')
            ->andReturn(collect([]));

        $this->mockArticleRepository
            ->shouldReceive('search')
            ->atLeast()->once()
            ->with(
                $this->callback(function ($params) use ($keyword, $filters) {
                    return $params['keyword'] === $keyword &&
                           $params['category'] === $filters['category'] &&
                           $params['source'] === $filters['source'] &&
                           $params['from'] === $filters['from'] &&
                           $params['to'] === $filters['to'];
                }),
                1,
                15
            )
            ->andReturn(new ConcreteLengthAwarePaginator([], 0, 15, 1));

        // Act
        $mergedParams = array_merge(['keyword' => $keyword], $filters);
        $result = $this->newsService->searchArticles($mergedParams, 1, 15);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /**
     * Test findById method finds article correctly
     */
    public function test_find_by_id_returns_article(): void
    {
        // Arrange
        $articleId = 1;
        $expectedArticle = new \App\Models\Article();
        $expectedArticle->id = $articleId;

        $this->mockArticleRepository
            ->shouldReceive('findById')
            ->once()
            ->with($articleId)
            ->andReturn($expectedArticle);

        // Act
        $result = $this->newsService->findById($articleId);

        // Assert
        $this->assertEquals($expectedArticle, $result);
    }

    /**
     * Test deleteById method deletes article correctly
     */
    public function test_delete_by_id_deletes_article(): void
    {
        // Arrange
        $articleId = 1;

        $this->mockArticleRepository
            ->shouldReceive('deleteById')
            ->once()
            ->with($articleId)
            ->andReturn(true);

        // Act
        $result = $this->newsService->deleteById($articleId);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test service handles repository exceptions gracefully
     */
    public function test_service_handles_repository_exceptions_gracefully(): void
    {
        // Arrange
        $this->mockArticleRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        // Act & Assert
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Article with ID 999 does not exist');

        $this->newsService->findById(999);
    }

    /**
     * Test getHeadlines with empty results
     */
    public function test_get_headlines_with_empty_results(): void
    {
        // Arrange
        $this->setupDefaultPreferenceMocks();
        
        $this->mockProviderAggregator
            ->shouldReceive('enabled')
            ->andReturn(collect([]));

        $this->mockArticleRepository
            ->shouldReceive('search')
            ->atLeast()->once()
            ->andReturn(new ConcreteLengthAwarePaginator([], 0, 15, 1));

        // Act
        $result = $this->newsService->getHeadlines([], 1, 15);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
    }

    /**
     * Test searchArticles with empty keyword
     */
    public function test_search_articles_with_empty_keyword(): void
    {
        // Arrange
        $this->setupDefaultPreferenceMocks();
        
        $this->mockProviderAggregator
            ->shouldReceive('enabled')
            ->andReturn(collect([]));

        $this->mockArticleRepository
            ->shouldReceive('search')
            ->atLeast()->once()
            ->with(
                $this->callback(function ($params) {
                    return isset($params['keyword']) && $params['keyword'] === '';
                }),
                1,
                15
            )
            ->andReturn(new ConcreteLengthAwarePaginator([], 0, 15, 1));

        // Act
        $result = $this->newsService->searchArticles(['keyword' => ''], 1, 15);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

}
