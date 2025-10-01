<?php

namespace Database\Seeders;

use App\Models\Preference;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Preference Seeder
 *
 * Seeds default preferences for the news aggregator.
 * Since no user authentication, creates a single preference record
 * with commonly used news sources, categories, and authors.
 *
 * @package Database\Seeders
 */
class PreferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing preferences first (singleton approach)
        Preference::truncate();

        // Create default preferences with popular news source and category
        Preference::create([
            'source' => 'BBC News',
            'category' => 'technology',
        ]);

        $this->command->info('Default preferences seeded successfully!');
    }
}
