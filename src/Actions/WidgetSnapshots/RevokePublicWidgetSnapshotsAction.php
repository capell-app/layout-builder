<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\WidgetSnapshots;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Facades\CapellCore;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\LayoutBuilder\Models\PublicWidgetSnapshot;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class RevokePublicWidgetSnapshotsAction
{
    use AsFake;
    use AsObject;

    public function handle(Pageable $page): int
    {
        if (! CapellCore::isPackageInstalled(LayoutBuilderServiceProvider::$packageName) || ! $page instanceof Model) {
            return 0;
        }

        return PublicWidgetSnapshot::query()
            ->where('pageable_type', $page->getMorphClass())
            ->where('pageable_id', $page->getKey())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'current_key' => null]);
    }
}
