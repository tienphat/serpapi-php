<?php

declare(strict_types=1);

namespace SerpApiOrg\Exceptions;

/**
 * Thrown when a cURL / network-level error occurs before any HTTP response.
 */
class NetworkException extends SerpApiException {}
