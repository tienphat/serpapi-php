<?php

declare(strict_types=1);

namespace SerpApiOrg\Exceptions;

/**
 * Thrown when the API returns HTTP 429.
 * Upgrade your plan or slow down requests.
 */
class RateLimitException extends SerpApiException {}
