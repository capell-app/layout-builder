<?php

declare(strict_types=1);

use Capell\Workspaces\WorkspaceRegistry;

it('Workspaces test suite boots correctly', function (): void {
    expect(WorkspaceRegistry::all())->not->toBeEmpty();
});
