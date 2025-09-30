<?php

namespace Database\Seeders;

use App\Services\SourceService;
use App\Integrations\News\Providers\NewsApiProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class SourceSeeder extends Seeder
{
    public function __construct(
        private SourceService $sourceService
    ) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding news sources...');
        
        // Fetch live sources from NewsAPI
        $this->seedNewsApiSources();
        
        // Add static sources for other providers
        $this->seedStaticSources();
        
        $totalSources = $this->sourceService->getActiveSourcesCount();
        $this->command->info("Seeded {$totalSources} news sources successfully.");
    }

    /**
     * Fetch and seed sources from NewsAPI
     */
    private function seedNewsApiSources(): void
    {
        $this->command->info('Fetching sources from NewsAPI...');
        
        try {
            $newsApiProvider = app(NewsApiProvider::class);
            $sources = $newsApiProvider->fetchSources();
            
            if ($sources->isEmpty()) {
                $this->command->warn('No sources fetched from NewsAPI - using fallback sources');
                $this->seedNewsApiFallback();
                return;
            }
            
            $count = 0;
            foreach ($sources as $sourceData) {
                $this->sourceService->updateOrCreateSource(
                    [
                        'source_id' => $sourceData['id'],
                        'provider' => 'newsapi'
                    ],
                    [
                        'name' => $sourceData['name'],
                        'description' => $sourceData['description'] ?? null,
                        'url' => $sourceData['url'] ?? null,
                        'category' => $sourceData['category'] ?? null,
                        'language' => $sourceData['language'] ?? 'en',
                        'country' => $sourceData['country'] ?? null,
                        'provider' => 'newsapi',
                        'is_active' => true,
                    ]
                );
                $count++;
            }
            
            $this->command->info("Seeded {$count} NewsAPI sources from live API");
            
        } catch (\Exception $e) {
            $this->command->error('Failed to fetch from NewsAPI: ' . $e->getMessage());
        }
    }

    /**
     * Seed static sources for other providers
     */
    private function seedStaticSources(): void
    {
        $staticSources = [
            [
                'source_id' => 'the-guardian',
                'name' => 'The Guardian',
                'description' => 'Latest news, sport, business, comment, analysis and reviews from the Guardian.',
                'url' => 'https://www.theguardian.com',
                'category' => 'general',
                'language' => 'en',
                'country' => 'gb',
                'provider' => 'guardian',
            ],
            [
                'source_id' => 'the-new-york-times',
                'name' => 'The New York Times',
                'description' => 'The New York Times: Find breaking news, multimedia, reviews & opinion on Washington, business, sports, movies, travel, books, jobs, education, real estate, cars & more at nytimes.com.',
                'url' => 'https://www.nytimes.com',
                'category' => 'general',
                'language' => 'en',
                'country' => 'us',
                'provider' => 'nyt',
            ],
        ];

        foreach ($staticSources as $source) {
            $this->sourceService->updateOrCreateSource(
                [
                    'source_id' => $source['source_id'],
                    'provider' => $source['provider']
                ],
                $source
            );
        }
        
        $this->command->info('Seeded ' . count($staticSources) . ' static sources (Guardian, NYT)');
    }
}