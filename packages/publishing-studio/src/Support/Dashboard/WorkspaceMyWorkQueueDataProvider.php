<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Support\Dashboard;

use Capell\Admin\Contracts\Dashboard\MyWorkQueueDataProvider;
use Capell\Admin\Data\Dashboard\MyWorkQueueData;
use Capell\PublishingStudio\Actions\Dashboard\BuildMyWorkQueueAction;
use Illuminate\Contracts\Auth\Authenticatable;

final class WorkspaceMyWorkQueueDataProvider implements MyWorkQueueDataProvider
{
    public function build(Authenticatable $user, int $limit): MyWorkQueueData
    {
        return BuildMyWorkQueueAction::run($user, $limit);
    }
}
