<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\Transactions;

use App\Exceptions\RateLimitExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transactions\CreateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final readonly class TransactionController extends Controller
{
    /**
     * GET /api/transactions
     *
     * Returns paginated transaction history and current balance
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $transactions = $user
            ->transactions()
            ->with(['sender:id,name,email', 'receiver:id,name,email'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'current_balance' => $user->fresh()->balance,
            'transactions' => [
                'data' => TransactionResource::collection($transactions),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'links' => $transactions->linkCollection(),
                ],
            ],
        ]);
    }

    /**
     * POST /api/transactions
     *
     * Creates a new money transfer with full security checks:
     * - Rate limiting: 10 transactions per minute per user
     * - Idempotency: Prevents duplicate transactions
     * - Distributed locking: Prevents race conditions
     * - Account locking: Fraud prevention
     * - Audit logging: Full transaction trail
     * - ...
     *
     * @throws InsufficientBalanceException
     * @throws UserLockedException
     * @throws DuplicateTransactionException
     * @throws RateLimitExceededException
     */
    public function store(CreateTransactionRequest $request, WalletService $walletService): JsonResponse
    {
        Gate::authorize('create', Transaction::class);

        $user = auth()->user();

        // Apply strict rate limiting for transactions (10 per minute)
        $this->applyRateLimit('transactions:create:' . $user->id, 10, 1);

        try {
            $transaction = $walletService->transfer(
                sender: $user,
                receiverId: $request->input('receiver_id'),
                amount: (float) $request->input('amount'),
                idempotencyKey: $request->input('idempotency_key')
            );

            return response()->json([
                'message' => 'Transaction completed successfully.',
                'transaction' => new TransactionResource($transaction),
                'new_balance' => (float) $user->fresh()->balance,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTrace(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Apply rate limiting with custom exception
     */
    private function applyRateLimit(string $key, int $maxAttempts, int $decayMinutes): void
    {
        $executed = RateLimiter::attempt(
            $key,
            $maxAttempts,
            function () {},
            $decayMinutes * 60
        );

        if (! $executed) {
            $availableIn = RateLimiter::availableIn($key);
            throw new RateLimitExceededException(
                "Too many requests. Please try again in {$availableIn} seconds."
            );
        }
    }
}
