<?php

declare(strict_types=1);

namespace Capell\Workspaces\Support\Dashboard;

use Capell\Admin\Contracts\Dashboard\RecentlyPublishedDataProvider;
use Capell\Admin\Data\Dashboard\RecentlyPublishedData;
use Capell\Workspaces\Actions\Dashboard\BuildRecentlyPublishedAction;

final class WorkspaceRecentlyPublishedDataProvider implements RecentlyPublishedDataProvider
{
    public function build(int $limit): RecentlyPublishedData
    {
        return BuildRecentlyPublishedAction::run($limit);
    }
}
