<?php

declare(strict_types=1);

use Capell\PublishingStudio\Contracts\ReleaseWorkspaceItemContributor;
use Capell\PublishingStudio\Data\ReleaseWorkspaceItemData;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\ReleaseWorkspaceItemRegistry;

it('registers release workspace item contributors in insertion order', function (): void {
    $registry = new ReleaseWorkspaceItemRegistry;
    $firstContributor = new class implements ReleaseWorkspaceItemContributor
    {
        public function itemsFor(Workspace $workspace): array
        {
            return [
                new ReleaseWorkspaceItemData(
                    source: 'test',
                    label: 'First item',
                    modelClass: Workspace::class,
                    modelId: 1,
                    changeType: 'updated',
                    status: 'ready',
                    url: null,
                ),
            ];
        }
    };
    $secondContributor = new class implements ReleaseWorkspaceItemContributor
    {
        public function itemsFor(Workspace $workspace): array
        {
            return [
                new ReleaseWorkspaceItemData(
                    source: 'test',
                    label: 'Second item',
                    modelClass: Workspace::class,
                    modelId: 2,
                    changeType: 'created',
                    status: 'ready',
                    url: null,
                ),
            ];
        }
    };

    $registry->register($firstContributor::class);
    $registry->register($secondContributor::class);

    expect($registry->contributors())->toBe([$firstContributor::class, $secondContributor::class]);
});

it('deduplicates contributor classes', function (): void {
    $registry = new ReleaseWorkspaceItemRegistry;

    $registry->register(ReleaseWorkspaceItemContributorFixture::class);
    $registry->register(ReleaseWorkspaceItemContributorFixture::class);

    expect($registry->contributors())->toBe([ReleaseWorkspaceItemContributorFixture::class]);
});

final class ReleaseWorkspaceItemContributorFixture implements ReleaseWorkspaceItemContributor
{
    public function itemsFor(Workspace $workspace): array
    {
        return [];
    }
}
