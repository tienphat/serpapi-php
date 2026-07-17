<?php

declare(strict_types=1);

namespace SerpApiOrg;

/**
 * Holds all client settings. Immutable — fluent setters return a new instance.
 *
 * Quick start:
 *   $config = SerpApiConfig::make('your-token')->country('US')->language('en');
 *
 * From a config file (Laravel / array):
 *   $config = SerpApiConfig::fromArray(config('serpapi'));
 */
final class SerpApiConfig
{
    private const DEFAULT_BASE_URL   = 'https://serpapi.org/api/v1';
    private const DEFAULT_TIMEOUT    = 30;
    private const DEFAULT_USER_AGENT = 'SerpApiOrg-PHP/1.0 (+https://github.com/serpapiorg/serpapi-php)';

    private string  $token;
    private string  $baseUrl;
    private int     $timeout;
    private ?string $country;
    private ?string $language;
    private string  $userAgent;

    private function __construct(string $token)
    {
        if (trim($token) === '') {
            throw new \InvalidArgumentException('SerpApi token must not be empty.');
        }

        $this->token     = $token;
        $this->baseUrl   = self::DEFAULT_BASE_URL;
        $this->timeout   = self::DEFAULT_TIMEOUT;
        $this->country   = null;
        $this->language  = null;
        $this->userAgent = self::DEFAULT_USER_AGENT;
    }

    // ── Factories ────────────────────────────────────────────────────────────

    public static function make(string $token): self
    {
        return new self($token);
    }

    /**
     * Build from an array — keys: token, base_url, timeout, country, language, user_agent.
     *
     * @param array{token: string, base_url?: string, timeout?: int, country?: string, language?: string, user_agent?: string} $config
     */
    public static function fromArray(array $config): self
    {
        if (empty($config['token'])) {
            throw new \InvalidArgumentException("Config array must contain a non-empty 'token' key.");
        }

        $instance = new self($config['token']);

        if (!empty($config['base_url']))   $instance->baseUrl   = rtrim((string) $config['base_url'], '/');
        if (isset($config['timeout']))     $instance->timeout   = (int) $config['timeout'];
        if (!empty($config['country']))    $instance->country   = (string) $config['country'];
        if (!empty($config['language']))   $instance->language  = (string) $config['language'];
        if (!empty($config['user_agent'])) $instance->userAgent = (string) $config['user_agent'];

        return $instance;
    }

    // ── Fluent setters (clone → immutable) ───────────────────────────────────

    /** Default country for every request. See config/serpapi-countries.php for codes. */
    public function country(string $country): self
    {
        $clone = clone $this;
        $clone->country = strtoupper($country);
        return $clone;
    }

    /** Default language for every request. See config/serpapi-languages.php for codes. */
    public function language(string $language): self
    {
        $clone = clone $this;
        $clone->language = strtolower($language);
        return $clone;
    }

    /** cURL timeout in seconds (minimum 1). */
    public function timeout(int $seconds): self
    {
        if ($seconds < 1) {
            throw new \InvalidArgumentException('Timeout must be at least 1 second.');
        }
        $clone = clone $this;
        $clone->timeout = $seconds;
        return $clone;
    }

    /** Override API base URL (for self-hosted or testing). */
    public function baseUrl(string $url): self
    {
        $clone = clone $this;
        $clone->baseUrl = rtrim($url, '/');
        return $clone;
    }

    // ── Getters ──────────────────────────────────────────────────────────────

    public function getToken(): string     { return $this->token;     }
    public function getBaseUrl(): string   { return $this->baseUrl;   }
    public function getTimeout(): int      { return $this->timeout;   }
    public function getCountry(): ?string  { return $this->country;   }
    public function getLanguage(): ?string { return $this->language;  }
    public function getUserAgent(): string { return $this->userAgent; }
}
