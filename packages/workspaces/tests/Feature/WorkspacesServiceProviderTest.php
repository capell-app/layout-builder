<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Workspaces\WorkspaceRegistry;

it('WorkspaceRegistry has Page registered after boot', function (): void {
    expect(WorkspaceRegistry::isRegistered(Page::class))->toBeTrue();
});
