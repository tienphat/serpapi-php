<?php

declare(strict_types=1);

namespace SerpApiOrg\Exceptions;

/**
 * Thrown when the API returns HTTP 401 or 403 (invalid/missing token).
 */
class SerpApiAuthException extends SerpApiException {}
