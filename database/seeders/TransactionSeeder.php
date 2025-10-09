<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Transaction;
use Illuminate\Database\Seeder;

final class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Transaction::factory(10)->create();
    }
}
