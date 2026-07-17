<?php

declare(strict_types=1);

namespace SerpApiOrg\Exceptions;

/**
 * Thrown when the API returns HTTP 429 (rate limit exceeded).
 */
class SerpApiRateLimitException extends SerpApiException {}
