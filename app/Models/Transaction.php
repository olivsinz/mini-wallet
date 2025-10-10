<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    /**
     * Commission rate (1.5%)
     */
    public const COMMISSION_RATE = 0.015;

    /**
     * The maximum amount that can be transferred in a single transaction.
     *
     * This value is used to validate incoming transactions and prevent large
     * transactions from being processed.
     */
    public const MAX_TRANSACTION_AMOUNT = 1_000_000;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'idempotency_key',
        'sender_id',
        'receiver_id',
        'amount',
        'commission_fee',
        'total_deducted',
        'status',
        'metadata',
        'processed_at',
    ];

    /**
     * Get the sender of the transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the receiver of the transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'metadata' => 'array',
            'amount' => 'decimal:2',
            'commission_fee' => 'decimal:2',
            'total_deducted' => 'decimal:2',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Check if the transaction is incoming for the given user id.
     */
    public function isIncoming(int $userId): bool
    {
        return $this->receiver_id === $userId;
    }

    /**
     * Check if the transaction is outgoing for the given user id.
     */
    public function isOutgoing(int $userId): bool
    {
        return $this->sender_id === $userId;
    }

    public function getTypeForUser(int $userId): string
    {
        return $this->isIncoming($userId) ? 'received' : 'sent';
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'rolled_back']);
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark the transaction as failed.
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Boot the model.
     *
     * Triggered by Eloquent's boot method, set a default idempotency_key if one is not provided.
     */
    protected static function boot()
    {
        parent::boot();

        self::creating(function ($transaction) {
            if (empty($transaction->idempotency_key)) {
                $transaction->idempotency_key = Str::uuid()->toString();
            }
        });
    }
}
