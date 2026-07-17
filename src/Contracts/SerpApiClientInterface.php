<?php

declare(strict_types=1);

namespace SerpApiOrg\Contracts;

use SerpApiOrg\SerpApiResponse;

/**
 * Contract for the SerpApiClient — useful for mocking in tests.
 */
interface SerpApiClientInterface
{
    public function webSearch(string $keyword, array $options = []): SerpApiResponse;
    public function imageSearch(string $keyword, array $options = []): SerpApiResponse;
    public function videoSearch(string $keyword, array $options = []): SerpApiResponse;
    public function newsSearch(string $keyword, array $options = []): SerpApiResponse;
    public function shoppingSearch(string $keyword, array $options = []): SerpApiResponse;
    public function autocomplete(string $keyword, array $options = []): SerpApiResponse;
    public function scholarSearch(string $keyword, array $options = []): SerpApiResponse;
    public function mapsSearch(string $keyword, array $options = []): SerpApiResponse;
    public function reviewsSearch(string $keyword, array $options = []): SerpApiResponse;
    public function search(string $keyword, array $options = []): SerpApiResponse;
    public function extractWebpage(string $url, bool $includeHtml = false): SerpApiResponse;
}
