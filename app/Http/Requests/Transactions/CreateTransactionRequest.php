<?php

declare(strict_types=1);

namespace App\Http\Requests\Transactions;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;

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
        if ($user->isLocked()) {
            return false;
        }

        return true;
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
                // 'min:0.01',
                'max:999999.99', // Limite transaction
                'regex:/^\d+(\.\d{1,2})?$/', // Max 2 décimales
            ],
            'idempotency_key' => [
                'nullable',
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
            'amount.min' => 'Amount must be at least 0.01.',
            'amount.gt' => 'The transaction amount must be greater than zero.',
            'amount.max' => 'Amount exceeds maximum transaction limit.',
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
        ]);
    }
}
