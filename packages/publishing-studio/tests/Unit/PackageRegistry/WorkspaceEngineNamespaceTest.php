<?php

declare(strict_types=1);
use Capell\PublishingStudio\Events\WorkspaceStateChanged;
use Capell\PublishingStudio\Publisher;
use Capell\PublishingStudio\WorkspaceContext;
use Capell\PublishingStudio\WorkspaceRegistry;

it('WorkspaceRegistry resolves from Capell\\PublishingStudio namespace', function (): void {
    expect(class_exists(WorkspaceRegistry::class))->toBeTrue();
});

it('WorkspaceContext resolves from Capell\\PublishingStudio namespace', function (): void {
    expect(class_exists(WorkspaceContext::class))->toBeTrue();
});

it('Publisher resolves from Capell\\PublishingStudio namespace', function (): void {
    expect(class_exists(Publisher::class))->toBeTrue();
});

it('WorkspaceStateChanged event resolves from Capell\\PublishingStudio namespace', function (): void {
    expect(class_exists(WorkspaceStateChanged::class))->toBeTrue();
});
