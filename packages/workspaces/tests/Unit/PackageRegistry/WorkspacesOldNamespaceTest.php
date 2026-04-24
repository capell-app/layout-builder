<?php

declare(strict_types=1);

it('has removed the old Capell\\Core\\Workspaces directory', function (): void {
    $workspacesDirectory = __DIR__ . '/../../../../../packages/core/src/Workspaces';

    expect(is_dir($workspacesDirectory))->toBeFalse();
});

it('has removed the old core Workspace model', function (): void {
    $workspaceModel = __DIR__ . '/../../../../../packages/core/src/Models/Workspace.php';

    expect(file_exists($workspaceModel))->toBeFalse();
});
