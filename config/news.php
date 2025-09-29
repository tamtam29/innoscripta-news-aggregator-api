<?php

return [
    'newsapi'  => ['base' => 'https://newsapi.org/v2', 'key' => env('NEWS_API_KEY', '')],
    'guardian' => ['base' => 'https://content.guardianapis.com', 'key' => env('GUARDIAN_API_KEY', '')],
    'nyt'      => ['base' => 'https://api.nytimes.com/svc', 'key' => env('NYT_API_KEY', '')],

    'enabled_providers' => ['newsapi'],

    'freshness' => [
        'headlines_minutes' => 15,
        'search_minutes'    => 60,
    ],
];
