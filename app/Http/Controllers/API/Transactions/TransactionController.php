<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\Request;

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
            ->latest()
            ->paginate(20);

        return response()->json([
            'current_balance' => $user->balance,
            'transactions' => TransactionResource::collection($transactions),
        ]);
    }

    public function store(): void {}
}
