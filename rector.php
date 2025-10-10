<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;

// use RectorLaravel\Set\LaravelSetList;
// use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withCache(
        cacheDirectory: '/tmp/rector',
        cacheClass: FileCacheStorage::class,
    )
    ->withSkip([
        AddOverrideAttributeToOverriddenMethodsRector::class,
    ])
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/bootstrap/app.php',
        __DIR__ . '/bootstrap/providers.php',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/public',
        __DIR__ . '/resources',
        __DIR__ . '/tests',
        __DIR__ . '/routes',
    ])
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        strictBooleans: true,
    );
// ->withSetProviders(LaravelSetProvider::class)
// ->withComposerBased(laravel: true)
// ->withSets([
//     LaravelSetList::LARAVEL_CODE_QUALITY,
//     LaravelSetList::LARAVEL_COLLECTION,
//     LaravelSetList::LARAVEL_ARRAYACCESS_TO_METHOD_CALL,
//     LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
//     LaravelSetList::LARAVEL_CONTAINER_STRING_TO_FULLY_QUALIFIED_NAME,
//     LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
//     LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
//     LaravelSetList::LARAVEL_FACTORIES,
//     LaravelSetList::LARAVEL_IF_HELPERS,
//     LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,
//     LaravelSetList::LARAVEL_STATIC_TO_INJECTION,
//     LaravelSetList::LARAVEL_TESTING,
//     LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
// ])
// ->withConfiguredRule(RemoveDumpDataDeadCodeRector::class, [
//     'dd', 'dump', 'var_dump',
// ])
// ->withConfiguredRule(RouteActionCallableRector::class)
// ->withConfiguredrule(WhereToWhereLikeRector::class);
