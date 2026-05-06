<?php

declare(strict_types=1);
use Capell\PublishingStudio\Enums\WorkspaceKindEnum;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\PreviewLink;
use Capell\PublishingStudio\Models\Version;
use Capell\PublishingStudio\Models\Workspace;

it('Workspace model resolves from Capell\\PublishingStudio\\Models namespace', function (): void {
    expect(class_exists(Workspace::class))->toBeTrue();
});

it('Version model resolves from Capell\\PublishingStudio\\Models namespace', function (): void {
    expect(class_exists(Version::class))->toBeTrue();
});

it('PreviewLink model resolves from Capell\\PublishingStudio\\Models namespace', function (): void {
    expect(class_exists(PreviewLink::class))->toBeTrue();
});

it('WorkspaceStatusEnum resolves from Capell\\PublishingStudio\\Enums namespace', function (): void {
    expect(enum_exists(WorkspaceStatusEnum::class))->toBeTrue();
});

it('WorkspaceKindEnum resolves from Capell\\PublishingStudio\\Enums namespace', function (): void {
    expect(enum_exists(WorkspaceKindEnum::class))->toBeTrue();
});
