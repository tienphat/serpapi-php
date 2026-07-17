# Changelog

All notable changes to `serpapiorg/serpapi-php` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] — 2025-07-17

### Added
- `SerpApiClient` class with methods for all 10 API endpoints:
  - `webSearch()`, `imageSearch()`, `videoSearch()`, `newsSearch()`
  - `shoppingSearch()`, `autocomplete()`, `scholarSearch()`
  - `mapsSearch()`, `reviewsSearch()`, `search()`, `extractWebpage()`
- Constructor-level default `gl` / `hl` locale injection
- Typed exceptions: `SerpApiException`, `SerpApiAuthException`, `SerpApiRateLimitException`
- Zero runtime dependencies — pure PHP + cURL
- PHPUnit test suite with unit and live integration tests
- GitHub Actions CI (PHP 8.1 / 8.2 / 8.3)
