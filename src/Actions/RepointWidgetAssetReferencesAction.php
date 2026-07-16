<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Contracts\WidgetAssetReferenceRepointer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class RepointWidgetAssetReferencesAction implements WidgetAssetReferenceRepointer
{
    use AsFake;
    use AsObject;

    public function handle(Model $asset, int|string $fromAssetId, int|string $toAssetId): int
    {
        return $this->repoint($asset, $fromAssetId, $toAssetId);
    }

    public function repoint(Model $asset, int|string $fromAssetId, int|string $toAssetId): int
    {
        if (! DB::getSchemaBuilder()->hasTable('widget_assets')) {
            return 0;
        }

        return DB::table('widget_assets')
            ->where('asset_type', $asset->getMorphClass())
            ->where('asset_id', $fromAssetId)
            ->update(['asset_id' => $toAssetId]);
    }
}
