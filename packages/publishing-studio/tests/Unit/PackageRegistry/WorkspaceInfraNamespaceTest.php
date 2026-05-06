<?php

declare(strict_types=1);
use Capell\PublishingStudio\Console\Commands\PruneAbandonedPublishingStudioCommand;
use Capell\PublishingStudio\Http\Middleware\ResolveWorkspaceContext;
use Capell\PublishingStudio\Listeners\StampWorkspaceOnActivity;

it('ResolveWorkspaceContext resolves from Capell\\PublishingStudio namespace', function (): void {
    expect(class_exists(ResolveWorkspaceContext::class))->toBeTrue();
});

it('PruneAbandonedPublishingStudioCommand resolves from Capell\\PublishingStudio namespace', function (): void {
    expect(class_exists(PruneAbandonedPublishingStudioCommand::class))->toBeTrue();
});

it('StampWorkspaceOnActivity resolves from Capell\\PublishingStudio namespace', function (): void {
    expect(class_exists(StampWorkspaceOnActivity::class))->toBeTrue();
});
