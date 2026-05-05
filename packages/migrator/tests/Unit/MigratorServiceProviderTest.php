<?php

declare(strict_types=1);

use Capell\Migrator\Contracts\MigratorContextResolver;
use Capell\Migrator\Contracts\MigratorRowContributor;
use Capell\Migrator\Contracts\PageCollisionDetector;
use Capell\Migrator\Services\Import\CsvReader;
use Capell\Migrator\Services\Import\Resolvers\RelationMatchResolverRegistry;
use Capell\Migrator\Services\Import\XmlReader;
use Capell\Migrator\Support\ImportSourceRegistry;

it('registers migrator config and default contracts', function (): void {
    expect(config('migrator.paths.exports'))->toBe('migrator/exports')
        ->and(resolve(MigratorContextResolver::class))->toBeInstanceOf(MigratorContextResolver::class)
        ->and(resolve(MigratorRowContributor::class))->toBeInstanceOf(MigratorRowContributor::class)
        ->and(resolve(PageCollisionDetector::class))->toBeInstanceOf(PageCollisionDetector::class);
});

it('registers default external import source readers', function (): void {
    $registry = resolve(ImportSourceRegistry::class);

    expect($registry->readers())
        ->toHaveCount(2)
        ->and($registry->readerFor('people.csv'))->toBeInstanceOf(CsvReader::class)
        ->and($registry->readerFor('content.xml'))->toBeInstanceOf(XmlReader::class);
});

it('registers default relation resolver groups', function (): void {
    $registry = resolve(RelationMatchResolverRegistry::class);

    expect($registry->hasGroup('layouts'))->toBeTrue()
        ->and($registry->hasGroup('types'))->toBeTrue()
        ->and($registry->hasGroup('sites'))->toBeTrue()
        ->and($registry->hasGroup('media'))->toBeTrue();
});
