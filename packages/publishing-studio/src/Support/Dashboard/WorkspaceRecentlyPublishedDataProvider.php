<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Support\Dashboard;

use Capell\Admin\Contracts\Dashboard\RecentlyPublishedDataProvider;
use Capell\Admin\Data\Dashboard\RecentlyPublishedData;
use Capell\PublishingStudio\Actions\Dashboard\BuildRecentlyPublishedAction;

final class WorkspaceRecentlyPublishedDataProvider implements RecentlyPublishedDataProvider
{
    public function build(int $limit): RecentlyPublishedData
    {
        return BuildRecentlyPublishedAction::run($limit);
    }
}
