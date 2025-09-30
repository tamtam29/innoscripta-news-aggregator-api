<?php

namespace App\Integrations\News\Supports;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Rate Limiting Support Trait
 * 
 * Provides rate limiting capabilities for news providers with:
 * - Request counting and throttling
 * - 429 error back-off strategy
 * - Rate limit header exposure
 * - Configurable limits per provider
 */
trait RateLimitTrait
{
    /**
     * Rate limit configuration for each provider
     */
    protected function getRateLimits(): array
    {
        return [
            'newsapi' => [
                'daily_limit' => 100,
                'minute_limit' => null,
                'second_limit' => null,
            ],
            'guardian' => [
                'daily_limit' => 500,
                'minute_limit' => null,
                'second_limit' => 1,
            ],
            'nyt' => [
                'daily_limit' => 500,
                'minute_limit' => 5,
                'second_limit' => null,
            ],
        ];
    }

    /**
     * Check if provider is currently rate limited
     */
    protected function isRateLimited(): bool
    {
        $providerKey = static::key();
        $limits = $this->getRateLimits()[$providerKey] ?? [];

        // Check daily limit
        if (isset($limits['daily_limit'])) {
            $dailyCount = Cache::get("rate_limit_daily_{$providerKey}", 0);
            if ($dailyCount >= $limits['daily_limit']) {
                Log::warning("[{$this->getProviderName()}] Daily rate limit ({$limits['daily_limit']}) exceeded: {$dailyCount} requests");
                return true;
            }
        }

        // Check minute limit
        if (isset($limits['minute_limit'])) {
            $minuteCount = Cache::get("rate_limit_minute_{$providerKey}", 0);
            if ($minuteCount >= $limits['minute_limit']) {
                Log::warning("[{$this->getProviderName()}] Per-minute rate limit ({$limits['minute_limit']}) exceeded: {$minuteCount} requests");
                return true;
            }
        }

        // Check second limit
        if (isset($limits['second_limit'])) {
            $secondCount = Cache::get("rate_limit_second_{$providerKey}", 0);
            if ($secondCount >= $limits['second_limit']) {
                Log::warning("[{$this->getProviderName()}] Per-second rate limit ({$limits['second_limit']}) exceeded: {$secondCount} requests");
                return true;
            }
        }

        return false;
    }

    /**
     * Increment rate limit counters
     */
    protected function incrementRateLimit(): void
    {
        $providerKey = static::key();
        $limits = $this->getRateLimits()[$providerKey] ?? [];

        // Increment daily counter
        if (isset($limits['daily_limit'])) {
            $dailyKey = "rate_limit_daily_{$providerKey}";
            $dailyCount = Cache::get($dailyKey, 0) + 1;
            Cache::put($dailyKey, $dailyCount, now()->endOfDay());
        }

        // Increment minute counter
        if (isset($limits['minute_limit'])) {
            $minuteKey = "rate_limit_minute_{$providerKey}";
            $minuteCount = Cache::get($minuteKey, 0) + 1;
            Cache::put($minuteKey, $minuteCount, 60); // 60 seconds
        }

        // Increment second counter
        if (isset($limits['second_limit'])) {
            $secondKey = "rate_limit_second_{$providerKey}";
            $secondCount = Cache::get($secondKey, 0) + 1;
            Cache::put($secondKey, $secondCount, 1); // 1 second
        }
    }

    /**
     * Get provider name for logging
     */
    protected function getProviderName(): string
    {
        return class_basename(static::class);
    }

    /**
     * Add throttling delay between requests
     */
    protected function throttleRequest(): void
    {
        $providerKey = static::key();
        $limits = $this->getRateLimits()[$providerKey] ?? [];

        // Add delay for per-second limits
        if (isset($limits['second_limit']) && $limits['second_limit'] === 1) {
            // For 1 req/sec limit, add 1 second delay
            sleep(1);
        }
        
        // Add delay for minute-based limits to spread requests
        if (isset($limits['minute_limit'])) {
            if ($limits['minute_limit'] === 5) {
                // For NYT's 5 req/minute limit, add 12 second delay between requests
                sleep(12);
            } elseif ($limits['minute_limit'] <= 10) {
                // For other minute-based limits <= 10, add 6 second delay between requests
                sleep(6);
            }
        }
    }
}