<?php

declare(strict_types=1);

namespace SerpApiOrg\Tests;

use PHPUnit\Framework\TestCase;
use SerpApiOrg\SerpApiClient;
use SerpApiOrg\Exceptions\SerpApiAuthException;
use SerpApiOrg\Exceptions\SerpApiException;

class SerpApiClientTest extends TestCase
{
    private function makeClient(string $token = 'test-token'): SerpApiClient
    {
        return new SerpApiClient($token);
    }

    public function test_constructor_throws_on_empty_token(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SerpApiClient('');
    }

    public function test_constructor_accepts_valid_token(): void
    {
        $client = $this->makeClient('valid-token-123');
        $this->assertInstanceOf(SerpApiClient::class, $client);
    }

    public function test_set_user_agent_returns_same_instance(): void
    {
        $client = $this->makeClient();
        $result = $client->setUserAgent('MyApp/1.0');
        $this->assertSame($client, $result);
    }

    /**
     * Integration-style test — skip if SERPAPI_TOKEN not set in env.
     *
     * Run with:  SERPAPI_TOKEN=your_key vendor/bin/phpunit --filter live_
     */
    public function live_web_search_returns_results(): void
    {
        $token = getenv('SERPAPI_TOKEN');
        if (!$token) {
            $this->markTestSkipped('SERPAPI_TOKEN env var not set.');
        }

        $client = new SerpApiClient($token);
        $result = $client->webSearch('php composer package');

        $this->assertArrayHasKey('data', $result);
        $this->assertNotEmpty($result['data']);
    }

    public function live_image_search_returns_results(): void
    {
        $token = getenv('SERPAPI_TOKEN');
        if (!$token) {
            $this->markTestSkipped('SERPAPI_TOKEN env var not set.');
        }

        $client = new SerpApiClient($token);
        $result = $client->imageSearch('nature wallpaper 4k');

        $this->assertArrayHasKey('data', $result);
        $this->assertNotEmpty($result['data']);
    }

    public function live_news_search_returns_results(): void
    {
        $token = getenv('SERPAPI_TOKEN');
        if (!$token) {
            $this->markTestSkipped('SERPAPI_TOKEN env var not set.');
        }

        $client = new SerpApiClient($token);
        $result = $client->newsSearch('artificial intelligence 2025');

        $this->assertArrayHasKey('data', $result);
        $this->assertNotEmpty($result['data']);
    }

    public function live_extract_webpage_returns_content(): void
    {
        $token = getenv('SERPAPI_TOKEN');
        if (!$token) {
            $this->markTestSkipped('SERPAPI_TOKEN env var not set.');
        }

        $client = new SerpApiClient($token);
        $result = $client->extractWebpage('https://example.com');

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('title', $result['data']);
    }
}
