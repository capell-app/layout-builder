<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions;

use Capell\PublishingStudio\Data\ReleaseWorkspaceItemData;
use Capell\PublishingStudio\Data\ReleaseWorkspaceSummaryData;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\ReleaseWorkspaceItemRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildReleaseWorkspaceSummaryAction
{
    use AsObject;

    public function handle(Workspace $workspace): ReleaseWorkspaceSummaryData
    {
        $items = [];
        $registry = app(ReleaseWorkspaceItemRegistry::class);

        foreach ($registry->contributors() as $contributorClass) {
            $contributor = app($contributorClass);

            foreach ($contributor->itemsFor($workspace) as $item) {
                if ($item instanceof ReleaseWorkspaceItemData) {
                    $items[] = $item;
                }
            }
        }

        return new ReleaseWorkspaceSummaryData(
            workspaceId: (int) $workspace->getKey(),
            items: $items,
            itemCount: count($items),
        );
    }
}
