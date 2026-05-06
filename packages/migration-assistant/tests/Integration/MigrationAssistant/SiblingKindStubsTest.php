<?php

declare(strict_types=1);

use Capell\MigrationAssistant\Exceptions\NotImplementedException;
use Capell\MigrationAssistant\Services\Import\SiteImportService;

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
