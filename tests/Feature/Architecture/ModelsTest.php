<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

arch('models')
    ->expect('App\Models')
    ->toHaveMethod('casts')
    ->toExtend(Illuminate\Database\Eloquent\Model::class)
    ->toOnlyBeUsedIn([
        'App\Concerns',
        'App\Console',
        'App\Filament',
        'App\Http',
        'App\Jobs',
        'App\Mail',
        'App\Models',
        'App\Notifications',
        'App\Observers',
        'App\Policies',
        'App\Providers',
        'App\Queries',
        'App\Rules',
        'App\Services',
        'Database\Factories',
        'Database\Seeders',
    ]);

arch('ensure factories', function (): void {
    expect($models = getModels())->toHaveCount(2);

    foreach ($models as $model) {
        /* @var \Illuminate\Database\Eloquent\Factories\HasFactory $model */
        expect($model::factory())
            ->toBeInstanceOf(Illuminate\Database\Eloquent\Factories\Factory::class);
    }
});

arch('ensure datetime casts', function (): void {
    expect($models = getModels())->toHaveCount(2);

    foreach ($models as $model) {
        /* @var \Illuminate\Database\Eloquent\Factories\HasFactory $model */
        $instance = $model::factory()->create();

        $dates = collect($instance->getAttributes())
            ->filter(fn ($_, $key): bool => str_ends_with((string) $key, '_at'))
            ->reject(fn ($_, $key): bool => in_array($key, ['created_at', 'updated_at']));

        foreach ($dates as $key => $value) {
            expect($instance->getCasts())->toHaveKey(
                $key,
                'datetime',
                sprintf(
                    'The %s cast on the %s model is not a datetime cast.',
                    $key,
                    $model,
                ),
            );
        }
    }
});

/**
 * Get all Eloquent model classes from app/Models (recursively).
 *
 * @return array<int, class-string<Illuminate\Database\Eloquent\Model>>
 */
function getModels(): array
{
    $modelFiles = File::allFiles(app_path('Models'));

    return collect($modelFiles)
        ->map(function ($file) {
            $relativePath = str_replace(
                [app_path() . DIRECTORY_SEPARATOR, '.php', '/'],
                ['', '', '\\'],
                $file->getPathname()
            );

            return 'App\\' . $relativePath;
        })
        ->filter(fn ($class): bool => is_subclass_of($class, Illuminate\Database\Eloquent\Model::class))
        ->values()
        ->toArray();
}
