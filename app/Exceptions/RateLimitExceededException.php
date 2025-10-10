<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Rate Limit Exceeded Exception
 */
final class RateLimitExceededException extends Exception
{
    public function __construct(
        string $message = 'Too many requests. Please try again later.',
        int $code = 429
    ) {
        parent::__construct($message, $code);
    }

    public function render()
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'rate_limit_exceeded',
        ], $this->getCode());
    }
}
