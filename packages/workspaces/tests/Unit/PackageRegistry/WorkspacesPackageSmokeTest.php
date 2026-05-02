<?php

declare(strict_types=1);

use Capell\Workspaces\Providers\WorkspacesServiceProvider;

it('workspaces service provider class exists in new namespace', function (): void {
    expect(class_exists(WorkspacesServiceProvider::class))->toBeTrue();
});
