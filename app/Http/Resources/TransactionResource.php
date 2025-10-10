<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userId = auth()->id();

        return [
            'id' => $this->id,
            'idempotency_key' => $this->idempotency_key,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'email' => $this->sender->email,
            ],
            'receiver' => [
                'id' => $this->receiver->id,
                'name' => $this->receiver->name,
                'email' => $this->receiver->email,
            ],
            'amount' => (float) $this->amount,
            'commission_fee' => (float) $this->commission_fee,
            'total_deducted' => (float) $this->total_deducted,
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
            'transaction_type' => $this->getTypeForUser($userId),
            // 'formatted_date' => $this->created_at->format('M d, Y H:i'),
        ];
    }
}
