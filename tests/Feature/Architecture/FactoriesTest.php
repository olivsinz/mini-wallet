<?php

declare(strict_types=1);

arch('factories')
    ->expect('Database\Factories')
    ->toExtend(Illuminate\Database\Eloquent\Factories\Factory::class)
    ->toHaveMethod('definition')
    ->toOnlyBeUsedIn([
        'App\Models',
    ]);
