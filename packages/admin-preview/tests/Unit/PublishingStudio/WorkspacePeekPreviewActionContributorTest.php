<?php

declare(strict_types=1);

use Capell\AdminPreview\Filament\Resources\PublishingStudio\Actions\WorkspacePeekPreviewAction;
use Capell\AdminPreview\PublishingStudio\WorkspacePeekPreviewActionContributor;
use Capell\PublishingStudio\Contracts\WorkspaceTableActionContributor;

it('implements the workspace table action contributor contract', function (): void {
    expect(WorkspacePeekPreviewActionContributor::class)
        ->toImplement(WorkspaceTableActionContributor::class);
});

it('is tagged as a workspace table action contributor', function (): void {
    $contributors = collect(app()->tagged(WorkspaceTableActionContributor::TAG))
        ->map(fn (object $contributor): string => $contributor::class)
        ->all();

    expect($contributors)->toContain(WorkspacePeekPreviewActionContributor::class);
});

it('contributes the workspace peek preview action', function (): void {
    $actions = (new WorkspacePeekPreviewActionContributor)->actions();

    expect($actions)->toHaveCount(1)
        ->and($actions[0])->toBeInstanceOf(WorkspacePeekPreviewAction::class);
});
