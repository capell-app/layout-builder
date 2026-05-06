<?php

declare(strict_types=1);

use Capell\MigrationAssistant\Contracts\MigrationAssistantContextResolver;
use Capell\MigrationAssistant\Contracts\MigrationAssistantRowContributor;
use Capell\MigrationAssistant\Contracts\PageCollisionDetector;
use Capell\MigrationAssistant\Services\Import\CsvReader;
use Capell\MigrationAssistant\Services\Import\Resolvers\RelationMatchResolverRegistry;
use Capell\MigrationAssistant\Services\Import\XmlReader;
use Capell\MigrationAssistant\Support\ImportSourceRegistry;

it('registers migration-assistant config and default contracts', function (): void {
    expect(config('migration-assistant.paths.exports'))->toBe('migration-assistant/exports')
        ->and(resolve(MigrationAssistantContextResolver::class))->toBeInstanceOf(MigrationAssistantContextResolver::class)
        ->and(resolve(MigrationAssistantRowContributor::class))->toBeInstanceOf(MigrationAssistantRowContributor::class)
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
