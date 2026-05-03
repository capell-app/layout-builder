<?php

declare(strict_types=1);

use Capell\Backup\Filament\Pages\ImportSitesPage;

it('throws RuntimeException when the page dispatches an import', function (): void {
    expect(fn () => (new ImportSitesPage)->runImport())
        ->toThrow(RuntimeException::class, 'Site imports are provided by the backup package.');
});
