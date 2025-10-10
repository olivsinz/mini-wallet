<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\TransactionProcessed;
use App\Exceptions\DuplicateTransactionException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\UserLockedException;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * CRITICAL POINT #1: Service Layer to isolate business logic
 *
 * Enhanced WalletService with comprehensive security features.
 *
 * Security Features:
 * 1. Idempotency - Prevents duplicate transactions
 * 2. Pessimistic Locking - SELECT ... FOR UPDATE to prevent race conditions
 * 3. Redis Distributed Locking - Additional layer of protection
 * 4. Database Transaction: Ensures atomicity (all-or-nothing)
 * 5. Account Locking - Fraud prevention
 * 6. Audit Logging - Full transaction trail for compliance
 * 7. Balance Caching - Redis cache cleared on balance changes
 * 8. Event Broadcasting: Real-time UI updates via Pusher
 * 8. Transaction Status Tracking - For rollbacks and monitoring
 */
final class WalletService
{
    private const LOCK_TIMEOUT_SECONDS = 10;

    private const CACHE_TTL_SECONDS = 60;

    /**
     * Calculates the commission fee for a given amount.
     * This is a static version of calculateCommission() and is used
     * internally by the WalletService.
     *
     * @param  float  $amount  The amount to calculate the commission for.
     * @return float The calculated commission fee.
     */
    public static function calculateCommissionStatic(float $amount): float
    {
        return round($amount * Transaction::COMMISSION_RATE, 2);
    }

    /**
     * Calculate total amount to be deducted (amount + commission).
     */
    public static function calculateTotalDeduction(float $amount): float
    {
        return $amount + self::calculateCommissionStatic($amount);
    }

    /**
     * CRITICAL POINT #2: Main transfer function
     *
     * Process a money transfer with full security checks
     *
     * COMPLETE FLOW:
     * 1. Check idempotence key
     * 2. Acquire Redis distributed lock
     * 3. Start DB transaction
     * 4. Acquire pessimistic locks (FOR UPDATE)
     * 5. Validate everything
     * 6. Update balances ATOMICALLY
     * 7. Create transaction record
     * 8. Commit
     * 9. Broadcast event
     *
     * @throws InsufficientBalanceException
     * @throws UserLockedException
     * @throws DuplicateTransactionException
     * @throws Throwable
     */
    public function transfer(
        User $sender,
        int $receiverId,
        float $amount,
        string $idempotencyKey
    ): Transaction {
        if ($amount < 0.00) {
            throw new InvalidArgumentException(sprintf(
                'Invalid amount: %s. Amount must be greater than zero.',
                $amount
            ));
        }

        if ($sender->id === $receiverId) {
            throw new InvalidArgumentException(sprintf(
                'Cannot transfer fund to self. (Sender: %s, Receiver: %s)',
                $sender->id,
                $receiverId
            ));
        }

        // CRITICAL POINT #3: Check idempotence (prevents double-clicking or duplicate transaction, retry)
        if ($existingTransaction = $this->findByIdempotencyKey($idempotencyKey)) {
            if ($existingTransaction->isFinal()) {
                throw new DuplicateTransactionException(
                    "Transaction already processed: {$idempotencyKey}"
                );
            }

            // If still processing, return existing transaction
            return $existingTransaction;
        }

        // CRITICAL POINT #4: Acquire distributed lock (with Redis) to prevent concurrent processing
        return $this->withDistributedLock($sender->id, function () use ($sender, $receiverId, $amount, $idempotencyKey) {

            // CRITICAL POINT #5: DB Transaction for atomicity
            return DB::transaction(function () use ($sender, $receiverId, $amount, $idempotencyKey) {

                // CRITICAL POINT #6: PESSIMISTIC LOCKING
                // We are Locking both user rows to prevent concurrent modifications.
                // This is CRITICAL for preventing race conditions in high-traffic scenarios.
                $lockedSender = User::where('id', $sender->id)
                    ->lockForUpdate()
                    ->first();

                $receiver = User::where('id', $receiverId)
                    ->lockForUpdate()
                    ->first();

                if (! $receiver) {
                    throw new InvalidArgumentException(
                        "Receiver not found with ID: {$receiverId}"
                    );
                }

                // CRITICAL POINT #7: Check user account locks
                if ($lockedSender->isLocked()) {
                    AuditLog::log('transaction_blocked_sender_locked', $lockedSender->id);
                    throw new UserLockedException('Your account is locked. Please contact support.');
                }

                if ($receiver->isLocked()) {
                    AuditLog::log('transaction_blocked_receiver_locked', $lockedSender->id);
                    throw new UserLockedException('Receiver account is locked.');
                }

                // Calculate commission fee and total deduction
                $commissionFee = $this->calculateCommission($amount);
                $totalDeducted = $amount + $commissionFee;

                // CRITICAL POINT #8: Balance validation AFTER lock
                // Validate sender has sufficient balance
                if (! $lockedSender->hasSufficientBalance($totalDeducted)) {
                    AuditLog::log('transaction_failed_insufficient_balance', $lockedSender->id, null, null, [
                        'required' => $totalDeducted,
                        'available' => $lockedSender->balance,
                    ]);

                    throw new InsufficientBalanceException(
                        "Insufficient balance. Required: {$totalDeducted}, Available: {$lockedSender->balance}"
                    );
                }

                // CRITICAL POINT #9: Create transaction BEFORE modifying balances
                $transaction = Transaction::create([
                    'idempotency_key' => $idempotencyKey,
                    'sender_id' => $lockedSender->id,
                    'receiver_id' => $receiver->id,
                    'amount' => $amount,
                    'commission_fee' => $commissionFee,
                    'total_deducted' => $totalDeducted,
                    'status' => 'pending',
                    // 'ip_address' => request()->ip(),
                    // 'user_agent' => request()->userAgent(),
                    'metadata' => [
                        'sender_balance_before' => $lockedSender->balance,
                        'receiver_balance_before' => $receiver->balance,
                    ],
                ]);

                try {
                    // CRITICAL POINT #10: ATOMIC modification of balances
                    $lockedSender->decrement('balance', $totalDeducted);
                    $receiver->increment('balance', $amount);

                    // Update last transaction timestamps
                    // $lockedSender->touchLastTransaction();
                    // $receiver->touchLastTransaction();

                    // Mark transaction as completed
                    $transaction->markAsCompleted();

                    // Clear cached balances
                    $this->clearBalanceCache($lockedSender->id);
                    $this->clearBalanceCache($receiver->id);

                    // Log successful transaction
                    AuditLog::log(
                        'transaction_completed',
                        $lockedSender->id,
                        'App\Models\Transaction',
                        $transaction->id,
                        [
                            'sender_balance_before' => $transaction->metadata['sender_balance_before'],
                            'receiver_balance_before' => $transaction->metadata['receiver_balance_before'],
                        ],
                        [
                            'sender_balance_after' => $lockedSender->fresh()->balance,
                            'receiver_balance_after' => $receiver->fresh()->balance,
                        ]
                    );

                    // CRITICAL POINT #11: Broadcast real-time event after commit
                    broadcast(new TransactionProcessed($transaction, $lockedSender, $receiver));

                    return $transaction->load(['sender', 'receiver']);

                } catch (Exception $e) {
                    // Mark transaction as failed
                    $transaction->markAsFailed();

                    AuditLog::log(
                        'transaction_failed',
                        $lockedSender->id,
                        'App\Models\Transaction',
                        $transaction->id,
                        null,
                        ['error' => $e->getMessage()]
                    );

                    throw $e;
                }
            }, 5); // Max 5 attempts for deadlock handling
        });
    }

    /**
     * Get user balance with Redis caching
     */
    public function getBalance(User $user): float
    {
        return (float) Cache::remember(
            $this->getBalanceCacheKey($user->id),
            self::CACHE_TTL_SECONDS,
            fn () => $user->fresh()->balance
        );
    }

    /**
     * Find transaction by idempotency key
     */
    private function findByIdempotencyKey(string $key): ?Transaction
    {
        return Transaction::where('idempotency_key', $key)->first();
    }

    /**
     * Distributed lock with Redis
     *
     * Execute provided callback with distributed lock to prevents concurrent processing.
     */
    private function withDistributedLock(int $userId, callable $callback)
    {
        $lockKey = "wallet:lock:user:{$userId}";
        $lock = Cache::lock($lockKey, self::LOCK_TIMEOUT_SECONDS);

        try {
            if (! $lock->get()) {
                throw new RuntimeException('Could not acquire lock. Please try again.');
            }

            return $callback();

        } finally {
            $lock->release();
        }
    }

    /**
     * Calculate commission
     */
    private function calculateCommission(float $amount): float
    {
        return round($amount * Transaction::COMMISSION_RATE, 2);
    }

    /**
     * Clear cached balance
     */
    private function clearBalanceCache(int $userId): void
    {
        Cache::forget($this->getBalanceCacheKey($userId));
    }

    /**
     * Get balance cache key
     */
    private function getBalanceCacheKey(int $userId): string
    {
        return "user:{$userId}:balance";
    }
}
