<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    /**
     * Commission rate (1.5%)
     */
    public const COMMISSION_RATE = 0.015;

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
        'meta',
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
            'meta' => 'array',
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
}
