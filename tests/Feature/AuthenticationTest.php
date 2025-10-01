<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Basic API Test
 *
 * Tests basic API functionality without authentication
 */
class AuthenticationTest extends TestCase
{
    /**
     * Test API sources endpoint is accessible
     */
    public function test_api_sources_endpoint_is_accessible(): void
    {
        // Act
        $response = $this->getJson('/api/sources');

        // Assert
        $response->assertOk();
    }

    /**
     * Test API headlines endpoint is accessible
     */
    public function test_api_headlines_endpoint_is_accessible(): void
    {
        // Act
        $response = $this->getJson('/api/news/headlines');

        // Assert
        $response->assertOk();
    }
}
