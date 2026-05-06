<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Support\Dashboard;

use Capell\Admin\Contracts\Dashboard\ContentHealthDataProvider;
use Capell\Admin\Data\Dashboard\ContentHealthData;
use Capell\PublishingStudio\Actions\Dashboard\BuildContentHealthAction;

final class WorkspaceContentHealthDataProvider implements ContentHealthDataProvider
{
    public function build(): ContentHealthData
    {
        return BuildContentHealthAction::run();
    }
}
