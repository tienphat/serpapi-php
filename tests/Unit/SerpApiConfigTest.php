<?php

declare(strict_types=1);

namespace SerpApiOrg\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SerpApiOrg\SerpApiConfig;

#[CoversClass(SerpApiConfig::class)]
class SerpApiConfigTest extends TestCase
{
    // ── make() ───────────────────────────────────────────────────────────────

    public function test_make_returns_config_instance(): void
    {
        $config = SerpApiConfig::make('tok-123');
        $this->assertInstanceOf(SerpApiConfig::class, $config);
    }

    public function test_make_stores_token(): void
    {
        $config = SerpApiConfig::make('my-secret-token');
        $this->assertSame('my-secret-token', $config->getToken());
    }

    public function test_make_throws_on_empty_token(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');
        SerpApiConfig::make('');
    }

    public function test_make_throws_on_whitespace_token(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SerpApiConfig::make('   ');
    }

    // ── Defaults ─────────────────────────────────────────────────────────────

    public function test_defaults_are_sensible(): void
    {
        $config = SerpApiConfig::make('tok');
        $this->assertSame(30, $config->getTimeout());
        $this->assertStringContainsString('serpapi.org/api/v1', $config->getBaseUrl());
        $this->assertNull($config->getCountry());
        $this->assertNull($config->getLanguage());
        $this->assertNotEmpty($config->getUserAgent());
    }

    // ── Fluent setters ───────────────────────────────────────────────────────

    public function test_country_returns_new_immutable_instance(): void
    {
        $original = SerpApiConfig::make('tok');
        $modified = $original->country('VN');

        $this->assertNotSame($original, $modified);
        $this->assertNull($original->getCountry());
        $this->assertSame('VN', $modified->getCountry());
    }

    public function test_country_normalises_to_uppercase(): void
    {
        $config = SerpApiConfig::make('tok')->country('gb');
        $this->assertSame('GB', $config->getCountry());
    }

    public function test_language_returns_new_immutable_instance(): void
    {
        $original = SerpApiConfig::make('tok');
        $modified = $original->language('vi');

        $this->assertNotSame($original, $modified);
        $this->assertNull($original->getLanguage());
        $this->assertSame('vi', $modified->getLanguage());
    }

    public function test_language_normalises_to_lowercase(): void
    {
        $config = SerpApiConfig::make('tok')->language('EN');
        $this->assertSame('en', $config->getLanguage());
    }

    public function test_timeout_stores_value(): void
    {
        $config = SerpApiConfig::make('tok')->timeout(15);
        $this->assertSame(15, $config->getTimeout());
    }

    public function test_timeout_throws_on_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SerpApiConfig::make('tok')->timeout(0);
    }

    public function test_timeout_throws_on_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SerpApiConfig::make('tok')->timeout(-5);
    }

    public function test_base_url_strips_trailing_slash(): void
    {
        $config = SerpApiConfig::make('tok')->baseUrl('https://example.com/api/v1/');
        $this->assertSame('https://example.com/api/v1', $config->getBaseUrl());
    }

    public function test_chaining_multiple_setters(): void
    {
        $config = SerpApiConfig::make('abc')
            ->country('US')
            ->language('en')
            ->timeout(10)
            ->baseUrl('https://api.test.dev/v1');

        $this->assertSame('abc', $config->getToken());
        $this->assertSame('US', $config->getCountry());
        $this->assertSame('en', $config->getLanguage());
        $this->assertSame(10, $config->getTimeout());
        $this->assertSame('https://api.test.dev/v1', $config->getBaseUrl());
    }

    // ── fromArray() ───────────────────────────────────────────────────────────

    public function test_from_array_builds_config(): void
    {
        $config = SerpApiConfig::fromArray([
            'token'    => 'arr-token',
            'country'  => 'DE',
            'language' => 'de',
            'timeout'  => 20,
            'base_url' => 'https://example.com/v1',
        ]);

        $this->assertSame('arr-token', $config->getToken());
        $this->assertSame('DE', $config->getCountry());
        $this->assertSame('de', $config->getLanguage());
        $this->assertSame(20, $config->getTimeout());
        $this->assertSame('https://example.com/v1', $config->getBaseUrl());
    }

    public function test_from_array_throws_when_token_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SerpApiConfig::fromArray(['country' => 'US']);
    }

    public function test_from_array_works_with_token_only(): void
    {
        $config = SerpApiConfig::fromArray(['token' => 'min-token']);
        $this->assertSame('min-token', $config->getToken());
    }
}
