<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Contracts;

use Capell\PublishingStudio\Data\ReleaseWorkspaceItemData;
use Capell\PublishingStudio\Models\Workspace;

interface ReleaseWorkspaceItemContributor
{
    /**
     * @return list<ReleaseWorkspaceItemData>
     */
    public function itemsFor(Workspace $workspace): array;
}
