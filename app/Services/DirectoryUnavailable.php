<?php

namespace App\Services;

use RuntimeException;

/**
 * The external org user directory could not be reached or is not configured.
 * The hub is designed to keep working without it — screens catch this and
 * show a "directory unavailable" state rather than erroring.
 */
class DirectoryUnavailable extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self('The user directory API is not configured. Set USER_API_ENDPOINT and USER_API_KEY.');
    }

    public static function unreachable(string $detail): self
    {
        return new self("The user directory API is unreachable: {$detail}");
    }
}
