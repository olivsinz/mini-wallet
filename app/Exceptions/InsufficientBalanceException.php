<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Custom exception for insufficient balance scenarios.
 */
final class InsufficientBalanceException extends Exception
{
    /**
     * Create a new InsufficientBalanceException instance.
     *
     * @param  string  $message  The exception message
     * @param  int  $code  The HTTP status code (default 422)
     */
    public function __construct(
        string $message = 'Insufficient balance to complete this transaction.',
        int $code = 422
    ) {
        parent::__construct($message, $code);
    }

    /**
     * Render the exception into an HTTP JSON response.
     *
     * @return JsonResponse
     */
    public function render()
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], $this->getCode());
    }
}
