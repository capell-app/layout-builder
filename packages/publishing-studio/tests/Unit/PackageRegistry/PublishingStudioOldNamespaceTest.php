<?php

declare(strict_types=1);

it('has removed the old Capell\\Core\\PublishingStudio directory', function (): void {
    $publishingStudioDirectory = __DIR__ . '/../../../../../packages/core/src/PublishingStudio';

    expect(is_dir($publishingStudioDirectory))->toBeFalse();
});

it('has removed the old core Workspace model', function (): void {
    $workspaceModel = __DIR__ . '/../../../../../packages/core/src/Models/Workspace.php';

    expect(file_exists($workspaceModel))->toBeFalse();
});
