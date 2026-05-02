<?php

declare(strict_types=1);

use Capell\Backup\Exceptions\NotImplementedException;
use Capell\Backup\Jobs\ExecuteWordPressImportJob;
use Capell\Backup\Models\BackupRestore;
use Capell\Backup\Services\Import\FieldMapper;
use Capell\Backup\Services\Import\SiteImportService;
use Capell\Backup\Services\Import\SpreadsheetReader;
use Capell\Backup\Services\Import\WpXmlReader;
use Capell\Backup\Services\Restore\RestoreService;

/*
 * These tests are deliberately skipped. Each is a placeholder for a
 * sibling ImportSessionKind service that ships real behaviour in a
 * later phase (H3/H4/H6). Keeping the tests here makes the enum/stubs
 * discoverable in the suite rather than dead code buried in src/.
 */

it('imports a site via SiteImportService', function (): void {
    expect(fn () => (new SiteImportService)->import())
        ->toThrow(NotImplementedException::class);
})->skip('Tracked in phase H3');

it('reads a WordPress WXR archive', function (): void {
    expect(fn () => (new WpXmlReader)->read())
        ->toThrow(NotImplementedException::class);
})->skip('Tracked in phase H4');

it('maps external fields onto the Capell schema', function (): void {
    expect(fn (): array => (new FieldMapper)->map())
        ->toThrow(NotImplementedException::class);
})->skip('Tracked in phase H4');

it('reads a spreadsheet import', function (): void {
    expect(fn () => (new SpreadsheetReader)->read())
        ->toThrow(NotImplementedException::class);
})->skip('Tracked in phase H4');

it('executes a WordPress import job', function (): void {
    expect(fn () => (new ExecuteWordPressImportJob(1))->handle())
        ->toThrow(NotImplementedException::class);
})->skip('Tracked in phase H4');

it('restores a full environment backup', function (): void {
    expect(fn () => (new RestoreService)->restore())
        ->toThrow(NotImplementedException::class);
})->skip('Tracked in phase H6');

it('persists a BackupRestore row for the FullRestore kind', function (): void {
    $restore = BackupRestore::query()->create([
        'source_archive_path' => 'backups/2026-01-01.zip',
    ]);

    expect($restore->status)->toBe('draft')
        ->and($restore->uuid)->not->toBeEmpty();
})->skip('Tracked in phase H6');
