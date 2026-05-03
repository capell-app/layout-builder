<?php

declare(strict_types=1);

use Capell\Backup\Filament\Resources\ImportSessions\ImportSessionResource;

it('registers the import sessions resource from the backup package', function (): void {
    expect(ImportSessionResource::shouldRegisterWithPanel())->toBeTrue();
});
