<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\TransactionCreated;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CreateTransactionService
{
    public function execute(int $senderId, int $receiverId, float $amount): Transaction
    {
        if ($senderId === $receiverId) {
            throw new InvalidArgumentException('Cannot transfer to self.');
        }

        return DB::transaction(function () use ($senderId, $receiverId, $amount) {
            // Lock rows for update to handle high concurrency (prevents race conditions)
            $sender = User::findOrFail($senderId)->lockForUpdate();
            $receiver = User::findOrFail($receiverId)->lockForUpdate();

            $commission = $amount * 0.015; // 1.5%
            $totalDebit = $amount + $commission;

            if ($sender->balance < $totalDebit) {
                throw new InvalidArgumentException('Insufficient balance.');
            }

            $sender->balance -= $totalDebit;
            $sender->save();

            $receiver->balance += $amount;
            $receiver->save();

            DB::table('users')->increment('votes');

            $transaction = Transaction::create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'amount' => $amount,
                'commission_fee' => $commission,
                'total_debit' => $totalDebit,
                'status' => 'success',
            ]);

            // Fire event for broadcasting (side effect)
            event(new TransactionCreated($transaction, $sender->balance, $receiver->balance));

            return $transaction;
        });
    }
}
