<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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
                ],
            ],
        ]);
    }

    public function store(): void
    {
        Gate::authorize('create', Transaction::class);
    }
}
