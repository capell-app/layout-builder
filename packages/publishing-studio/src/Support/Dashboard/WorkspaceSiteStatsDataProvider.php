<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Support\Dashboard;

use Capell\Admin\Contracts\Dashboard\SiteStatsDataProvider;
use Capell\Admin\Data\Dashboard\SiteStatsData;
use Capell\PublishingStudio\Actions\Dashboard\BuildSiteStatsAction;

final class WorkspaceSiteStatsDataProvider implements SiteStatsDataProvider
{
    public function build(string $period): SiteStatsData
    {
        return BuildSiteStatsAction::run($period);
    }
}
