<?php

declare(strict_types=1);

use Capell\Backup\Contracts\BackupContextResolver;
use Capell\Backup\Contracts\BackupRowContributor;
use Capell\Backup\Contracts\PageCollisionDetector;
use Capell\Backup\Services\Import\Resolvers\RelationMatchResolverRegistry;

it('registers backup config and default contracts', function (): void {
    expect(config('backup.paths.exports'))->toBe('backup/exports')
        ->and(resolve(BackupContextResolver::class))->toBeInstanceOf(BackupContextResolver::class)
        ->and(resolve(BackupRowContributor::class))->toBeInstanceOf(BackupRowContributor::class)
        ->and(resolve(PageCollisionDetector::class))->toBeInstanceOf(PageCollisionDetector::class);
});

it('registers default relation resolver groups', function (): void {
    $registry = resolve(RelationMatchResolverRegistry::class);

    expect($registry->hasGroup('layouts'))->toBeTrue()
        ->and($registry->hasGroup('types'))->toBeTrue()
        ->and($registry->hasGroup('sites'))->toBeTrue()
        ->and($registry->hasGroup('media'))->toBeTrue();
});
