<?php

declare(strict_types=1);

use Capell\Migrator\Filament\Resources\ImportSessions\ImportSessionResource;

it('registers the import sessions resource from the migrator package', function (): void {
    expect(ImportSessionResource::shouldRegisterWithPanel())->toBeTrue();
});
