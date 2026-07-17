# serpapi-php

<p align="center">
  <a href="https://serpapi.org"><img src="https://serpapi.org/img/logo.png" width="100" alt="SerpApi.Org"></a>
</p>

<p align="center">
  <a href="https://github.com/tienphat/serpapi-php/actions"><img src="https://github.com/tienphat/serpapi-php/workflows/Tests/badge.svg" alt="CI"></a>
  <a href="https://packagist.org/packages/tienphat/serpapi-php"><img src="https://img.shields.io/packagist/v/tienphat/serpapi-php" alt="Version"></a>
  <a href="https://packagist.org/packages/tienphat/serpapi-php"><img src="https://img.shields.io/packagist/dt/tienphat/serpapi-php" alt="Downloads"></a>
  <a href="LICENSE"><img src="https://img.shields.io/packagist/l/tienphat/serpapi-php" alt="MIT"></a>
  <img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4" alt="PHP 8.1+">
</p>

<p align="center">
  Official PHP client for <a href="https://serpapi.org"><strong>SerpApi.Org</strong></a> — real-time Bing search results as structured JSON.<br>
  Web · Images · Videos · News · Shopping · Scholar · Maps · Reviews · Autocomplete · Webpage
</p>

---

## Highlights

| | |
|---|---|
| 🆓 **5,000 free queries/month** | No credit card required |
| ⚡ **Sub-second responses** | Live data, no stale cache |
| 🌍 **Country & language targeting** | `gl` + `hl` on every call |
| 📦 **Zero runtime dependencies** | Pure PHP + cURL |
| 🔒 **Type-safe** | Typed exceptions, typed response wrapper |
| 🧪 **Fully tested** | 61 tests, 93 assertions |

---

## Requirements

- PHP **8.1+**
- `ext-curl`, `ext-json`

---

## Installation

```bash
composer require tienphat/serpapi-php
```

---

## Quick Start

```php
use SerpApiOrg\SerpApiClient;

$client = new SerpApiClient('YOUR_TOKEN');

$results = $client->webSearch('laravel framework');

foreach ($results as $item) {
    echo $item['title'] . ' — ' . $item['link'] . PHP_EOL;
}
```

Get your free token at **[serpapi.org](https://serpapi.org)** → Sign in → API Key.

---

## Configuration

### Token only (simplest)

```php
$client = new SerpApiClient('YOUR_TOKEN');
```

### With default locale (fluent)

```php
use SerpApiOrg\SerpApiConfig;
use SerpApiOrg\SerpApiClient;

$config = SerpApiConfig::make('YOUR_TOKEN')
    ->country('US')   // applied to every call unless overridden
    ->language('en')
    ->timeout(15);    // seconds

$client = new SerpApiClient($config);
```

### From a config file (Laravel / framework)

```php
// config/serpapi.php
return [
    'token'    => env('SERPAPI_TOKEN'),
    'country'  => env('SERPAPI_COUNTRY', 'US'),
    'language' => env('SERPAPI_LANGUAGE', 'en'),
    'timeout'  => 30,
];

// Boot
$client = new SerpApiClient(SerpApiConfig::fromArray(config('serpapi')));
```

Publish the built-in config stub:

```bash
php artisan vendor:publish --tag=serpapi-config
```

---

## All Endpoints

| Method | Description |
|--------|-------------|
| `webSearch($kw, $opts)` | Organic results — titles, links, snippets |
| `imageSearch($kw, $opts)` | Images with thumbnails & source URLs |
| `videoSearch($kw, $opts)` | Videos — publisher, views, duration |
| `newsSearch($kw, $opts)` | Articles with source & date |
| `shoppingSearch($kw, $opts)` | Products — price, merchant, thumbnail |
| `autocomplete($kw, $opts)` | Search suggestions |
| `scholarSearch($kw, $opts)` | Google Scholar — citations, year, PDF |
| `mapsSearch($kw, $opts)` | Local places — address, phone, hours, coords |
| `reviewsSearch($kw, $opts)` | Review results with rating signals |
| `search($kw, $opts)` | Unified auto-detect endpoint |
| `extractWebpage($url, $html?)` | Content, metadata, images & links from any URL |

**Common `$opts` keys:**

| Key | Type | Notes |
|-----|------|-------|
| `gl` | `string` | Country code — see [`config/serpapi-countries.php`](config/serpapi-countries.php) |
| `hl` | `string` | Language code — see [`config/serpapi-languages.php`](config/serpapi-languages.php) |
| `size` | `int` | Results per page (max 100) |
| `page` | `int` | Page number (1-based) |

---

## The Response Object

Every method returns a `SerpApiResponse`. You never need to touch the raw array directly.

```php
$res = $client->webSearch('php 8.4');

// Iterate directly
foreach ($res as $item) { … }

// Access data
$res->data();          // list<array>  — all result items
$res->first();         // array|null   — first item
$res->pluck('link');   // list<string> — one field across all items
$res->count();         // int          — same as count($res)
$res->isEmpty();       // bool

// Metadata
$res->request();       // the params echoed back by the API
$res->inSeconds();     // float — API latency
$res->meta();          // ['total_results' => …, 'page' => …]

// Serialise
$res->toArray();       // full raw response
$res->toJson();        // pretty-printed JSON string
```

---

## Usage Examples

### Web search

```php
$res = $client->webSearch('best PHP frameworks 2025', ['gl' => 'US', 'size' => 10]);

foreach ($res as $item) {
    echo $item['title'] . PHP_EOL;
    echo $item['link']  . PHP_EOL;
}
```

### Image search

```php
$res = $client->imageSearch('northern lights 4k', ['gl' => 'US', 'size' => 20]);

foreach ($res as $img) {
    echo $img['title']     . PHP_EOL;
    echo $img['image_url'] . PHP_EOL;
}
```

### News search

```php
$res = $client->newsSearch('AI regulation 2025', ['gl' => 'US', 'hl' => 'en']);

foreach ($res as $article) {
    printf("[%s] %s — %s\n", $article['source'], $article['title'], $article['date']);
}
```

### Shopping search

```php
$res = $client->shoppingSearch('MacBook Pro M4', ['gl' => 'US', 'size' => 5]);

foreach ($res as $product) {
    printf("%s | %s | %s\n", $product['title'], $product['price'], $product['seller']);
}
```

### Scholar search

```php
$res = $client->scholarSearch('attention is all you need', ['size' => 5]);

foreach ($res as $paper) {
    echo $paper['title'] . " ({$paper['year']}) — cited by {$paper['citedBy']}\n";
    if (!empty($paper['pdfUrl'])) {
        echo 'PDF: ' . $paper['pdfUrl'] . "\n";
    }
}
```

### Maps / local search

```php
$res = $client->mapsSearch('coffee near Brooklyn NY', ['gl' => 'US', 'size' => 5]);

foreach ($res as $place) {
    printf("%s | %s | %s\n", $place['title'], $place['address'], $place['open_status']);
}
```

### Webpage extraction

```php
$res = $client->extractWebpage('https://techcrunch.com/some-article/');

$data = $res->toArray()['data'];

echo $data['title'] . PHP_EOL;
echo 'Author : ' . ($data['metadata']['author']     ?? '—') . PHP_EOL;
echo 'Words  : ' . ($data['metadata']['word_count'] ?? '—') . PHP_EOL;
echo PHP_EOL . $data['content'];
```

### Per-call locale override

```php
// Config has default country=US, but override per call:
$res = $client->webSearch('football', ['gl' => 'GB', 'hl' => 'en']);
```

### Paginate

```php
for ($page = 1; $page <= 3; $page++) {
    $res = $client->webSearch('open source PHP', ['page' => $page, 'size' => 10]);
    foreach ($res as $item) { … }
}
```

### Pluck a field across results

```php
$links = $client->webSearch('laravel docs')->pluck('link');
// ['https://laravel.com', 'https://laravel.com/docs', …]
```

---

## Error Handling

```php
use SerpApiOrg\Exceptions\AuthException;
use SerpApiOrg\Exceptions\RateLimitException;
use SerpApiOrg\Exceptions\NetworkException;
use SerpApiOrg\Exceptions\SerpApiException;

try {
    $res = $client->webSearch('query');
} catch (AuthException $e) {
    // HTTP 401/403 — invalid or expired token
    // → check https://serpapi.org/api-key
} catch (RateLimitException $e) {
    // HTTP 429 — quota exceeded
    // → upgrade plan or add a delay
    sleep(2);
} catch (NetworkException $e) {
    // cURL failed before any HTTP response
} catch (SerpApiException $e) {
    // Any other API error (4xx / 5xx / bad JSON)
    echo $e->getMessage() . ' (HTTP ' . $e->getCode() . ')';
}
```

**Exception hierarchy:**

```
\RuntimeException
  └── SerpApiException        ← base, all API errors
        ├── AuthException     ← 401 / 403
        ├── RateLimitException← 429
        └── NetworkException  ← cURL / connection failure
```

---

## Testing

```bash
# Unit tests — no API token needed
composer test

# Live integration (requires SERPAPI_TOKEN in env)
SERPAPI_TOKEN=your_token vendor/bin/phpunit --filter live_
```

---

## Package Structure

```
serpapi-php/
├── config/
│   ├── serpapi.php              # Main config (token, country, language, timeout)
│   ├── serpapi-countries.php    # 47 country codes (gl)
│   └── serpapi-languages.php    # 54 language codes (hl)
├── src/
│   ├── SerpApiClient.php        # Main client
│   ├── SerpApiConfig.php        # Immutable config value object
│   ├── SerpApiResponse.php      # Typed response wrapper
│   ├── Contracts/
│   │   └── SerpApiClientInterface.php
│   └── Exceptions/
│       ├── SerpApiException.php
│       ├── AuthException.php
│       ├── RateLimitException.php
│       └── NetworkException.php
└── tests/Unit/
    ├── SerpApiClientTest.php    # 22 tests
    ├── SerpApiConfigTest.php    # 19 tests
    └── SerpApiResponseTest.php  # 20 tests
```

---

## Supported Countries & Languages

Full reference tables are included in the package:

- **Countries (`gl`)** → [`config/serpapi-countries.php`](config/serpapi-countries.php) — 47 countries
- **Languages (`hl`)** → [`config/serpapi-languages.php`](config/serpapi-languages.php) — 54 languages

Common values at a glance:

| Country | `gl` | | Language | `hl` |
|---------|------|-|----------|------|
| United States | `US` | | English | `en` |
| United Kingdom | `GB` | | Vietnamese | `vi` |
| Vietnam | `VN` | | French | `fr` |
| Germany | `DE` | | German | `de` |
| Japan | `JP` | | Japanese | `ja` |
| France | `FR` | | Spanish | `es` |
| India | `IN` | | Hindi | `hi` |
| Brazil | `BR` | | Portuguese (BR) | `pt-br` |

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

---

## License

MIT — see [LICENSE](LICENSE).

---

<p align="center">
  <a href="https://serpapi.org">serpapi.org</a> ·
  <a href="https://serpapi.org/docs">Docs</a> ·
  <a href="https://github.com/tienphat/serpapi-php/issues">Issues</a> ·
  <a href="https://t.me/peterpanpro">Telegram</a>
</p>
