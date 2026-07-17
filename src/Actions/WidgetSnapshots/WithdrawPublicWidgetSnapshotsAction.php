<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\WidgetSnapshots;

use Capell\Core\Contracts\Pageable;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final readonly class WithdrawPublicWidgetSnapshotsAction
{
    use AsFake;
    use AsObject;

    public function handle(Pageable $page): int
    {
        return RevokePublicWidgetSnapshotsAction::run($page);
    }
}
