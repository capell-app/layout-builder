<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\WidgetSnapshots;

use Capell\LayoutBuilder\Models\PublicWidgetSnapshot;
use Lorisleiva\Actions\Concerns\AsObject;

final class PrunePublicWidgetSnapshotsAction
{
    use AsObject;

    public function handle(): int
    {
        $deleted = PublicWidgetSnapshot::query()
            ->where(function ($query): void {
                $query->where('expires_at', '<=', now())->orWhereNotNull('revoked_at');
            })
            ->delete();

        return is_int($deleted) ? $deleted : 0;
    }
}
