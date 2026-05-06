<?php

declare(strict_types=1);

use Capell\PublishingStudio\WorkspaceRegistry;

it('PublishingStudio test suite boots correctly', function (): void {
    expect(WorkspaceRegistry::all())->not->toBeEmpty();
});
