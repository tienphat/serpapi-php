<?php

declare(strict_types=1);

namespace SerpApiOrg;

use SerpApiOrg\Contracts\SerpApiClientInterface;
use SerpApiOrg\Exceptions\AuthException;
use SerpApiOrg\Exceptions\NetworkException;
use SerpApiOrg\Exceptions\RateLimitException;
use SerpApiOrg\Exceptions\SerpApiException;

/**
 * Official PHP client for SerpApi.Org.
 *
 * Simple:  new SerpApiClient('your-token')
 * Config:  new SerpApiClient(SerpApiConfig::make('token')->country('US'))
 * Laravel: new SerpApiClient(SerpApiConfig::fromArray(config('serpapi')))
 *
 * @link https://serpapi.org/docs
 */
class SerpApiClient implements SerpApiClientInterface
{
    private SerpApiConfig $config;

    /** @param SerpApiConfig|string $config Token string or a SerpApiConfig object. */
    public function __construct(SerpApiConfig|string $config)
    {
        $this->config = is_string($config) ? SerpApiConfig::make($config) : $config;
    }

    // ── Endpoints ────────────────────────────────────────────────────────────

    /** Organic web results — titles, links, snippets. */
    public function webSearch(string $keyword, array $options = []): SerpApiResponse
    {
        return $this->call('webs-search', ['keyword' => $keyword] + $options);
    }

    /** Image results with thumbnails and source URLs. */
    public function imageSearch(string $keyword, array $options = []): SerpApiResponse
    {
        return $this->call('images-search', ['keyword' => $keyword] + $options);
    }

    /** Video results — publisher, views, duration. */
    public function videoSearch(string $keyword, array $options = []): SerpApiResponse
    {
        return $this->call('videos-search', ['keyword' => $keyword] + $options);
    }

    /** News articles with source and publication date. */
    public function newsSearch(string $keyword, array $options = []): SerpApiResponse
    {
        return $this->call('news-search', ['keyword' => $keyword] + $options);
    }

    /** Shopping products — price, merchant, thumbnail. */
    public function shoppingSearch(string $keyword, array $options = []): SerpApiResponse
    {
        return $this->call('shopping-search', ['keyword' => $keyword] + $options);
    }

    /** Search suggestions / autocomplete. */
    public function autocomplete(string $keyword, array $options = []): SerpApiResponse
    {
        return $this->call('autocomplete', ['keyword' => $keyword] + $options);
    }

    /** Google Scholar — citations, year, PDF links. */
    public function scholarSearch(string $keyword, array $options = []): SerpApiResponse
    {
        return $this->call('scholar-search', ['keyword' => $keyword] + $options);
    }

    /** Local/maps places — address, phone, hours, coordinates. */
    public function mapsSearch(string $keyword, array $options = []): SerpApiResponse
    {
        return $this->call('maps-search', ['keyword' => $keyword] + $options);
    }

    /** Review-focused results with rating signals. */
    public function reviewsSearch(string $keyword, array $options = []): SerpApiResponse
    {
        return $this->call('reviews-search', ['keyword' => $keyword] + $options);
    }

    /** Unified endpoint — auto-detects result type. */
    public function search(string $keyword, array $options = []): SerpApiResponse
    {
        return $this->call('search', ['keyword' => $keyword] + $options);
    }

    /** Extract content, metadata, images and links from any URL. */
    public function extractWebpage(string $url, bool $includeHtml = false): SerpApiResponse
    {
        $params = ['url' => $url];
        if ($includeHtml) {
            $params['include_html'] = 'true';
        }
        return $this->call('webpage', $params);
    }

    // ── Internals ────────────────────────────────────────────────────────────

    private function call(string $endpoint, array $params): SerpApiResponse
    {
        if ($this->config->getCountry() && !isset($params['gl'])) {
            $params['gl'] = $this->config->getCountry();
        }
        if ($this->config->getLanguage() && !isset($params['hl'])) {
            $params['hl'] = $this->config->getLanguage();
        }

        $params['token'] = $this->config->getToken();
        $url = $this->config->getBaseUrl() . '/' . ltrim($endpoint, '/') . '?' . http_build_query($params);

        [$raw, $httpCode] = $this->send($url);

        return $this->parseResponse($raw, $httpCode);
    }

    /** @return array{0: string, 1: int} */
    protected function send(string $url): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => $this->config->getTimeout(),
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'User-Agent: ' . $this->config->getUserAgent(),
            ],
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($errno || $body === false) {
            throw new NetworkException("Network error: {$error} (errno {$errno})");
        }

        return [(string) $body, $code];
    }

    /** @throws SerpApiException */
    protected function parseResponse(string $body, int $httpCode): SerpApiResponse
    {
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SerpApiException("Invalid JSON from API (HTTP {$httpCode}): " . json_last_error_msg());
        }

        $message = (string) ($data['message'] ?? $data['error'] ?? "HTTP {$httpCode}");

        match (true) {
            $httpCode === 401,
            $httpCode === 403 => throw new AuthException($message, $httpCode),
            $httpCode === 429 => throw new RateLimitException($message, $httpCode),
            $httpCode >= 400  => throw new SerpApiException($message, $httpCode),
            default           => null,
        };

        return new SerpApiResponse($data);
    }
}
