<?php

declare(strict_types=1);

namespace App\Http\Requests\Transactions;

use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * @method \App\Models\User user()
 */
final class CreateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(#[CurrentUser] User $user): bool
    {
        return $user->isNotLocked();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(#[CurrentUser] User $user): array
    {
        return [
            'receiver_id' => [
                'required',
                'integer',
                'exists:users,id',
                'different:' . $user->id,
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:2',
                'max:' . Transaction::MAX_TRANSACTION_AMOUNT,
            ],
            'idempotency_key' => [
                'required',
                'string',
                'uuid',
            ],
        ];
    }

    /**
     * Customize the validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'receiver_id.required' => 'Please specify a recipient.',
            'receiver_id.exists' => 'The specified recipient does not exist.',
            'receiver_id.different' => 'You cannot send money to yourself.',
            'amount.required' => 'Please specify an amount to send.',
            'amount.gt' => 'The transaction amount must be greater than zero.',
            'amount.max' => 'Amount exceeds your maximum transaction limit.',
            'amount.regex' => 'Amount can have at most 2 decimal places.',
            'idempotency_key.uuid' => 'Invalid idempotency key format.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    public function prepareForValidation(): void
    {
        $this->merge([
            'sender_id' => $this->user()?->id,
            'amount' => (float) $this->input('amount'),
        ]);
    }

    /**
     * Custom validation after standard rules pass.
     * Checks if sender has sufficient balance including commission.
     */
    public function after(#[CurrentUser] User $user): array
    {
        return [
            function (Validator $validator) use ($user): void {

                $this->ensureBalanceIsSufficient($user, $validator);

                $this->ensureReceiverIsNotLocked($validator);

                $amount = (float) $this->input('amount');

                // Basic fraud detection
                // Transaction > 50% of balance = suspicious
                if ($amount > ($user->balance * 0.5)) {
                    AuditLog::log(
                        'large_transaction_attempted',
                        $user->id,
                        null,
                        null,
                        ['amount' => $amount, 'balance' => $user->balance]
                    );
                }
            },
        ];
    }

    /**
     * Ensures the recipient account is not currently locked by
     * adding an error to the validator if so. This check is done
     * after the standard validation rules have passed.
     */
    private function ensureReceiverIsNotLocked(Validator $validator): void
    {
        $receiver = User::find($this->input('receiver_id'));

        if ($receiver && $receiver->isLocked()) {
            $validator->errors()->add(
                'receiver_id',
                'The recipient account is currently locked.'
            );
        }
    }

    /**
     * Checks if the user has sufficient balance including commission
     * and adds an error to the validator if not.
     */
    private function ensureBalanceIsSufficient(User $user, Validator $validator): void
    {
        $amount = (float) $this->input('amount');

        $totalRequired = WalletService::calculateTotalDeduction($amount);

        if ($user->balance < $totalRequired) {
            $commissionRate = Transaction::COMMISSION_RATE * 100;

            $validator->errors()->add(
                'amount',
                "Insufficient balance. Required: {$totalRequired} (including {$commissionRate}% commission), Available: {$user->balance}"
            );
        }
    }
}
