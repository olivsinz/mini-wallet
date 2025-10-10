<?php

declare(strict_types=1);

use App\Http\Controllers\API\Auth\AuthenticatedSessionController;
use App\Http\Controllers\API\Transactions\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login');

Route::middleware('throttle:api')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', fn (Request $request) => $request->user());

        Route::apiResource('transactions', TransactionController::class)->only(['index', 'store']);

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    });
});
