<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class UserLockedException extends Exception
{
    public function __construct(
        string $message = 'Account is locked. Please contact support.',
        int $code = 403
    ) {
        parent::__construct($message, $code);
    }

    public function render()
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'account_locked',
        ], $this->getCode());
    }
}
