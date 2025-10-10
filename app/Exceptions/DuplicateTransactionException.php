<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class DuplicateTransactionException extends Exception
{
    public function __construct(
        string $message = 'This transaction has already been processed.',
        int $code = 409
    ) {
        parent::__construct($message, $code);
    }

    public function render()
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'duplicate_transaction',
        ], $this->getCode());
    }
}
