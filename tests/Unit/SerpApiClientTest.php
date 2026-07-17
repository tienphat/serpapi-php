<?php

declare(strict_types=1);

namespace SerpApiOrg\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SerpApiOrg\Contracts\SerpApiClientInterface;
use SerpApiOrg\Exceptions\AuthException;
use SerpApiOrg\Exceptions\NetworkException;
use SerpApiOrg\Exceptions\RateLimitException;
use SerpApiOrg\Exceptions\SerpApiException;
use SerpApiOrg\SerpApiClient;
use SerpApiOrg\SerpApiConfig;
use SerpApiOrg\SerpApiResponse;

/** HTTP calls are intercepted by a CapturingClient/stub subclass — no network required. */
#[CoversClass(SerpApiClient::class)]
class SerpApiClientTest extends TestCase
{

    // ── Constructor ───────────────────────────────────────────────────────────

    public function test_accepts_token_string(): void
    {
        $client = new SerpApiClient('my-token');
        $this->assertInstanceOf(SerpApiClient::class, $client);
    }

    public function test_accepts_config_object(): void
    {
        $config = SerpApiConfig::make('cfg-token');
        $client = new SerpApiClient($config);
        $this->assertInstanceOf(SerpApiClient::class, $client);
    }

    public function test_throws_on_empty_token_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SerpApiClient('');
    }

    public function test_implements_interface(): void
    {
        $client = new SerpApiClient('tok');
        $this->assertInstanceOf(SerpApiClientInterface::class, $client);
    }

    // ── Successful responses ──────────────────────────────────────────────────

    public function test_web_search_returns_response(): void
    {
        $client = $this->stubClient(200, $this->fakeWebPayload());
        $res    = $client->webSearch('laravel');

        $this->assertInstanceOf(SerpApiResponse::class, $res);
        $this->assertSame(2, $res->count());
        $this->assertSame('Laravel - The PHP Framework', $res->first()['title']);
    }

    public function test_image_search_returns_response(): void
    {
        $client = $this->stubClient(200, $this->fakeImagePayload());
        $res    = $client->imageSearch('php logo');

        $this->assertFalse($res->isEmpty());
        $this->assertArrayHasKey('image_url', $res->first());
    }

    public function test_news_search_returns_response(): void
    {
        $client = $this->stubClient(200, $this->fakeNewsPayload());
        $res    = $client->newsSearch('ai news');

        $this->assertSame(2, $res->count());
        $this->assertArrayHasKey('source', $res->first());
    }

    public function test_extract_webpage_returns_response(): void
    {
        $client = $this->stubClient(200, $this->fakeWebpagePayload());
        $res    = $client->extractWebpage('https://example.com');

        $this->assertSame('Example Domain', $res->toArray()['data']['title']);
    }

    public function test_response_exposes_request_params(): void
    {
        $client = $this->stubClient(200, [
            'request'    => ['keyword' => 'test', 'gl' => 'US', 'token' => 'xxx'],
            'data'       => [],
            'in_seconds' => 0.1,
        ]);

        $res = $client->webSearch('test');
        $this->assertSame('test', $res->request()['keyword']);
    }

    public function test_response_exposes_latency(): void
    {
        $client = $this->stubClient(200, $this->fakeWebPayload());
        $this->assertSame(0.35, $client->webSearch('x')->inSeconds());
    }

    public function test_foreach_iterates_results(): void
    {
        $client = $this->stubClient(200, $this->fakeWebPayload());
        $titles = [];

        foreach ($client->webSearch('x') as $item) {
            $titles[] = $item['title'];
        }

        $this->assertCount(2, $titles);
    }

    public function test_pluck_extracts_links(): void
    {
        $client = $this->stubClient(200, $this->fakeWebPayload());
        $links  = $client->webSearch('x')->pluck('link');

        $this->assertSame(['https://laravel.com', 'https://laravel.com/docs'], $links);
    }

    // ── Exception mapping ─────────────────────────────────────────────────────

    public function test_throws_auth_exception_on_401(): void
    {
        $this->expectException(AuthException::class);
        $this->expectExceptionCode(401);

        $this->stubClient(401, ['message' => 'Invalid token'])->webSearch('x');
    }

    public function test_throws_auth_exception_on_403(): void
    {
        $this->expectException(AuthException::class);
        $this->expectExceptionCode(403);

        $this->stubClient(403, ['message' => 'Forbidden'])->webSearch('x');
    }

    public function test_throws_rate_limit_exception_on_429(): void
    {
        $this->expectException(RateLimitException::class);
        $this->expectExceptionCode(429);

        $this->stubClient(429, ['message' => 'Quota exceeded'])->webSearch('x');
    }

    public function test_throws_serp_api_exception_on_500(): void
    {
        $this->expectException(SerpApiException::class);
        $this->expectExceptionCode(500);

        $this->stubClient(500, ['message' => 'Server error'])->webSearch('x');
    }

    public function test_throws_serp_api_exception_on_invalid_json(): void
    {
        $this->expectException(SerpApiException::class);
        $this->expectExceptionMessage('Invalid JSON');

        $this->stubClientRaw(200, 'not-json')->webSearch('x');
    }

    public function test_throws_network_exception_on_curl_error(): void
    {
        $this->expectException(NetworkException::class);
        $this->stubClientNetworkError()->webSearch('x');
    }

    // ── Default locale injection ───────────────────────────────────────────────

    public function test_default_country_is_injected_into_url(): void
    {
        $config = SerpApiConfig::make('tok')->country('VN');
        $client = $this->stubClientCapture($config);

        $client->webSearch('test');

        $this->assertStringContainsString('gl=VN', $client->lastUrl());
    }

    public function test_per_call_country_overrides_default(): void
    {
        $config = SerpApiConfig::make('tok')->country('US');
        $client = $this->stubClientCapture($config);

        $client->webSearch('test', ['gl' => 'GB']);

        $this->assertStringContainsString('gl=GB', $client->lastUrl());
        $this->assertStringNotContainsString('gl=US', $client->lastUrl());
    }

    public function test_default_language_is_injected(): void
    {
        $config = SerpApiConfig::make('tok')->language('vi');
        $client = $this->stubClientCapture($config);

        $client->webSearch('test');

        $this->assertStringContainsString('hl=vi', $client->lastUrl());
    }

    public function test_token_is_always_appended_to_url(): void
    {
        $client = $this->stubClientCapture(SerpApiConfig::make('secret-key'));
        $client->webSearch('test');

        $this->assertStringContainsString('token=secret-key', $client->lastUrl());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function stubClient(int $httpCode, array $body): SerpApiClient
    {
        return new class(SerpApiConfig::make('test-token'), $httpCode, json_encode($body)) extends SerpApiClient {
            public function __construct(
                SerpApiConfig $config,
                private int $code,
                private string $body,
            ) {
                parent::__construct($config);
            }

            protected function send(string $url): array { return [$this->body, $this->code]; }
        };
    }

    private function stubClientRaw(int $httpCode, string $rawBody): SerpApiClient
    {
        return new class(SerpApiConfig::make('test-token'), $httpCode, $rawBody) extends SerpApiClient {
            public function __construct(
                SerpApiConfig $config,
                private int $code,
                private string $body,
            ) {
                parent::__construct($config);
            }

            protected function send(string $url): array { return [$this->body, $this->code]; }
        };
    }

    private function stubClientNetworkError(): SerpApiClient
    {
        return new class(SerpApiConfig::make('test-token')) extends SerpApiClient {
            public function __construct(SerpApiConfig $config)
            {
                parent::__construct($config);
            }

            protected function send(string $url): array
            {
                throw new NetworkException('Connection refused');
            }
        };
    }

    private function stubClientCapture(SerpApiConfig $config): CapturingClient
    {
        return new CapturingClient($config);
    }


    // ── Fake payloads ─────────────────────────────────────────────────────────

    private function fakeWebPayload(): array
    {
        return [
            'request'    => ['keyword' => 'laravel', 'gl' => 'US'],
            'data'       => [
                ['position' => 1, 'title' => 'Laravel - The PHP Framework', 'link' => 'https://laravel.com'],
                ['position' => 2, 'title' => 'Laravel Docs', 'link' => 'https://laravel.com/docs'],
            ],
            'in_seconds' => 0.35,
        ];
    }

    private function fakeImagePayload(): array
    {
        return [
            'data'       => [['title' => 'PHP Logo', 'image_url' => 'https://php.net/logo.png']],
            'in_seconds' => 0.5,
        ];
    }

    private function fakeNewsPayload(): array
    {
        return [
            'data' => [
                ['title' => 'AI takes over', 'source' => 'TechCrunch', 'date' => '2025-07-17'],
                ['title' => 'AI news 2', 'source' => 'Wired', 'date' => '2025-07-16'],
            ],
            'in_seconds' => 0.4,
        ];
    }

    private function fakeWebpagePayload(): array
    {
        return [
            'data'       => ['title' => 'Example Domain', 'content' => 'This domain is for use in examples.'],
            'in_seconds' => 0.28,
        ];
    }
}

// ── Test helpers (defined outside the test class) ────────────────────────────

/**
 * Named subclass that captures the URL built by SerpApiClient::call()
 * so URL-construction tests can assert on it without hitting the network.
 */
final class CapturingClient extends SerpApiClient
{
    private string $capturedUrl = '';

    public function __construct(SerpApiConfig $config)
    {
        parent::__construct($config);
    }

    protected function send(string $url): array
    {
        $this->capturedUrl = $url;
        return [json_encode(['data' => [], 'in_seconds' => 0.0, 'request' => []]), 200];
    }

    public function lastUrl(): string
    {
        return $this->capturedUrl;
    }
}
