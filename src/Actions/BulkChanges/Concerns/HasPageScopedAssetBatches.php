<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\BulkChanges\Concerns;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Illuminate\Database\Eloquent\Model;

/** @internal */
trait HasPageScopedAssetBatches
{
    /** @return array<string, list<int|string>> */
    private function pageIdsByTypeForLayout(Layout $layout): array
    {
        $pageIdsByType = [];

        foreach (array_unique(CapellCore::getPageVariationModels()) as $pageModel) {
            if (! is_a($pageModel, Model::class, true)) {
                continue;
            }

            $pageModel::query()->where('layout_id', $layout->id)->get(['id'])->each(function (Model $page) use (&$pageIdsByType): void {
                $pageKey = $page->getKey();

                if (is_int($pageKey) || is_string($pageKey)) {
                    $pageIdsByType[$page->getMorphClass()][] = $pageKey;
                }
            });
        }

        return $pageIdsByType;
    }

    /** @return int<1, 1000> */
    private function assetChunkSize(): int
    {
        $configuredSize = config('capell-layout-builder.bulk_change_asset_delete_chunk_size');

        return min(1000, max(1, is_numeric($configuredSize) ? (int) $configuredSize : 500));
    }
}
