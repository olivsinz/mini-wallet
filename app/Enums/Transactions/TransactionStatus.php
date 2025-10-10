<?php

declare(strict_types=1);

namespace App\Enums\Transactions;

use Illuminate\Support\Str;

/**
 * TransactionStatus Enum
 *
 * Represents the lifecycle states of a wallet transaction.
 */
enum TransactionStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REVERSED = 'reversed';

    /**
     * Converts the enum to array for UI/API responses.
     */
    public static function toArray(): array
    {
        return array_map(fn (\App\Enums\Transactions\TransactionStatus $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }

    /**
     * Returns all values as a simple array.
     */
    public static function values(): array
    {
        return array_map(fn (\App\Enums\Transactions\TransactionStatus $case) => $case->value, self::cases());
    }

    /**
     * Returns a TransactionStatus from a string safely.
     */
    public static function fromString(?string $value): ?self
    {
        if (in_array($value, [null, '', '0'], true)) {
            return null;
        }

        $value = Str::lower($value);

        return collect(self::cases())
            ->first(fn ($case): bool => $case->value === $value);
    }

    /**
     * Returns a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::REVERSED => 'Reversed',
        };
    }

    /**
     * Returns true if the transaction is final (cannot be changed anymore).
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::COMPLETED, self::FAILED, self::REVERSED => true,
            default => false,
        };
    }

    /**
     * Returns true if the transaction was successful.
     */
    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Returns true if the transaction can still be modified or cancelled.
     */
    public function isPendingOrProcessing(): bool
    {
        return in_array($this, [self::PENDING, self::PROCESSING], true);
    }
}
