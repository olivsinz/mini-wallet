<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get the transactions sent by the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Transaction>
     */
    public function sentTransactions()
    {
        return $this->hasMany(Transaction::class, 'sender_id');
    }

    /**
     * Get the transactions received by the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Transaction>
     */
    public function receivedTransactions()
    {
        return $this->hasMany(Transaction::class, 'receiver_id');
    }

    /**
     * Get the user's transactions (as sender or receiver).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Transaction>
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'sender_id')->orWhere('receiver_id', $this->id);
    }

    /**
     * Determine if the user account is locked.
     */
    public function isLocked(): bool
    {
        return $this->is_locked;
    }

    /**
     * Determine if the user account is not locked.
     */
    public function isNotLocked(): bool
    {
        return ! $this->islocked();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2',
            'is_locked' => 'boolean',
        ];
    }

    /**
     * Check if the user is the sender or receiver of the given transaction.
     */
    public function isTransactionSenderOrReceiver(Transaction $transaction): bool
    {
        return $this->id === $transaction->sender_id || $this->id === $transaction->receiver_id;
    }

    /**
     * Check if the user is the author (sender) of the given transaction.
     */
    public function isTransactionAuthor(Transaction $transaction): bool
    {
        return $this->id === $transaction->sender_id;
    }

    /**
     * Check if user has sufficient balance for a transaction.
     *
     * @param  float  $amount  Total amount to deduct (including commission)
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Get the channels the user receives notifications on.
     */
    public function receivesBroadcastNotificationsOn(): string
    {
        return 'users.' . $this->id;
    }

    /**
     * Lock account (fraud prevention)
     */
    public function lock(): void
    {
        $this->update(['is_locked' => true]);
    }

    /**
     * Unlock account
     */
    public function unlock(): void
    {
        $this->update(['is_locked' => false]);
    }
}
