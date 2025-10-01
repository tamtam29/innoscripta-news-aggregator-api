<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\SourceSeeder;
use Database\Seeders\PreferenceSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SourceSeeder::class,
            PreferenceSeeder::class,
        ]);
    }
}
