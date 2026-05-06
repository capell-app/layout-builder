<?php

declare(strict_types=1);

use Capell\MigrationAssistant\Filament\Resources\ImportSessions\ImportSessionResource;

it('registers the import sessions resource from the migration-assistant package', function (): void {
    expect(ImportSessionResource::shouldRegisterWithPanel())->toBeTrue();
});
