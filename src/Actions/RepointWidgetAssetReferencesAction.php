<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

final class RepointWidgetAssetReferencesAction
{
    use AsAction;

    public function handle(Model $asset, int|string $fromAssetId, int|string $toAssetId): int
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
