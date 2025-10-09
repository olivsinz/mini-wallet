<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel()->ignoring([App\Providers\AppServiceProvider::class]);
arch()->preset()->strict()->ignoring([App\Http\Controllers\Controller::class]);

arch('ensure no extends')
    ->expect('App')
    ->classes()
    ->not->toBeAbstract()
    ->ignoring([
        App\Http\Controllers\Controller::class,
    ]);

arch('avoid mutation')
    ->expect('App')
    ->classes()
    ->toBeReadonly()
    ->ignoring([
        'App\Exceptions',
        'App\Faker',
        'App\Filament',
        'App\Http\Middleware\HandleInertiaRequests',
        'App\Http\Requests',
        'App\Http\Resources',
        'App\Jobs',
        'App\Models',
        'App\Providers',
    ]);

arch('avoid inheritance')
    ->expect('App')
    ->classes()
    ->toExtendNothing()
    ->ignoring([
        'App\Console\Commands',
        'App\Exceptions',
        'App\Faker',
        'App\Filament',
        'App\Http\Controllers',
        'App\Http\Resources',
        'App\Http\Requests',
        'App\Http\Middleware\HandleInertiaRequests',
        'App\Jobs',
        'App\Livewire',
        'App\Mail',
        'App\Models',
        'App\Notifications',
        'App\Providers',
    ]);

arch('ensure annotations for properties and methods documented')
    ->expect('App')
    ->toHavePropertiesDocumented()
    ->ignoring('App\Providers')
    ->toHaveMethodsDocumented()
    ->ignoring('App\Providers');
