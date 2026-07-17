<?php

declare(strict_types=1);

namespace SerpApiOrg\Exceptions;

/**
 * Thrown when the API returns HTTP 401 or 403.
 * Check your token at: https://serpapi.org/api-key
 */
class AuthException extends SerpApiException {}
