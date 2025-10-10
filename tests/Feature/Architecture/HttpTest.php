<?php

declare(strict_types=1);

arch('controllers')
    ->expect('App\Http\Controllers')
    ->toExtend(\App\Http\Controllers\Controller::class);

arch('middleware')
    ->skip('To do')
    ->expect('App\Http\Middleware')
    ->toHaveMethod('handle')
    ->toUse(\Illuminate\Http\Request::class)
    ->not->toBeUsed();

arch('requests')
    ->expect('App\Http\Requests')
    ->toExtend(\Illuminate\Foundation\Http\FormRequest::class)
    ->toHaveMethod('rules')
    ->toBeUsedIn('App\Http\Controllers');

arch()
    ->expect('App\Http')
    ->toOnlyBeUsedIn('App\Http');
