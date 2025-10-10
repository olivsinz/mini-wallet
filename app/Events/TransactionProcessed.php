<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * TransactionProcessed event is broadcast after a successful transaction.
 *
 * Uses ShouldBroadcastNow to ensure immediate broadcasting (no queue delay).
 * Broadcasts to private channels of both sender and receiver.
 */
final class TransactionProcessed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public User $sender,
        public User $receiver
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * Broadcasts to private channels for both users involved.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.' . $this->sender->id),
            new PrivateChannel('users.' . $this->receiver->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'transaction.processed';
    }

    /**
     * Get the data to broadcast.
     *
     * Includes transaction details and updated balances.
     */
    public function broadcastWith(): array
    {
        return [
            'transaction' => [
                'id' => $this->transaction->id,
                'sender_id' => $this->transaction->sender_id,
                'receiver_id' => $this->transaction->receiver_id,
                'amount' => (float) $this->transaction->amount,
                'commission_fee' => (float) $this->transaction->commission_fee,
                'total_deducted' => (float) $this->transaction->total_deducted,
                'status' => $this->transaction->status,
                'processed_at' => $this->transaction->processed_at,
                'created_at' => $this->transaction->created_at->toIso8601String(),
            ],
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'balance' => (float) $this->sender->fresh()->balance,
            ],
            'receiver' => [
                'id' => $this->receiver->id,
                'name' => $this->receiver->name,
                'balance' => (float) $this->receiver->fresh()->balance,
            ],
        ];
    }
}
