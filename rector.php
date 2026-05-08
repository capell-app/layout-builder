<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodingStyle\Rector\PostInc\PostIncDecToPreIncDecRector;
use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\PHPUnit\CodeQuality\Rector\CallLike\DirectInstanceOverMockArgRector;
use RectorLaravel\Set\LaravelSetList;
use RectorLaravel\Set\LaravelSetProvider;
use Sinnbeck\DomAssertions\Rector\Rules\AssertElementToAssertContainsElementRule;

return RectorConfig::configure()
    ->withSetProviders(LaravelSetProvider::class)
    ->withSets([
        LaravelSetList::LARAVEL_ARRAYACCESS_TO_METHOD_CALL,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_CONTAINER_STRING_TO_FULLY_QUALIFIED_NAME,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
        LaravelSetList::LARAVEL_FACTORIES,
        LaravelSetList::LARAVEL_IF_HELPERS,
        LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,
    ])
    ->withImportNames(
        removeUnusedImports: true,
    )
    ->withCache(
        cacheDirectory: '/tmp/rector',
        cacheClass: FileCacheStorage::class,
    )
    ->withPaths([
        __DIR__ . '/packages',
        __DIR__ . '/tests',
    ])
    ->withParallel(
        timeoutSeconds: 600,
        maxNumberOfProcess: 8,
        jobSize: 8,
    )
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
        carbon: true,
        rectorPreset: true,
        phpunitCodeQuality: true,
        doctrineCodeQuality: true,
        symfonyCodeQuality: true,
        symfonyConfigs: true,
    )
    ->withRules([
        AddOverrideAttributeToOverriddenMethodsRector::class,
        AssertElementToAssertContainsElementRule::class,
    ])
    ->withSkip([
        PostIncDecToPreIncDecRector::class,
        ClosureToArrowFunctionRector::class,
        ArrayToFirstClassCallableRector::class,
        ReadOnlyClassRector::class,
        ReadOnlyPropertyRector::class,
        DirectInstanceOverMockArgRector::class,
        NullToStrictStringFuncCallArgRector::class => [
            __DIR__ . '/packages/insights/routes/web.php',
            __DIR__ . '/packages/layout-builder/src/Livewire/Filament/Concerns/ManagesAssets.php',
            __DIR__ . '/packages/layout-builder/src/Livewire/Widget/Pages.php',
        ],
        __DIR__ . '/tests/.pest',
    ])
    ->withPhpSets();
