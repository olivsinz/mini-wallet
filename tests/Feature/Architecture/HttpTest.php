<?php

declare(strict_types=1);

arch('controllers')
    ->expect('App\Http\Controllers')
    ->toExtend('App\Http\Controllers\Controller');

arch('middleware')
    ->skip('To do')
    ->expect('App\Http\Middleware')
    ->toHaveMethod('handle')
    ->toUse('Illuminate\Http\Request')
    ->not->toBeUsed();

arch('requests')
    ->expect('App\Http\Requests')
    ->toExtend('Illuminate\Foundation\Http\FormRequest')
    ->toHaveMethod('rules')
    ->toBeUsedIn('App\Http\Controllers');

arch()
    ->expect('App\Http')
    ->toOnlyBeUsedIn('App\Http');
