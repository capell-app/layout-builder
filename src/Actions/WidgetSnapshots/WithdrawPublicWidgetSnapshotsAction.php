<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\WidgetSnapshots;

use Capell\Core\Contracts\Pageable;
use Lorisleiva\Actions\Concerns\AsObject;

final readonly class WithdrawPublicWidgetSnapshotsAction
{
    use AsObject;

    public function __construct(private RevokePublicWidgetSnapshotsAction $revoker) {}

    public function handle(Pageable $page): int
    {
        return $this->revoker->handle($page);
    }
}
