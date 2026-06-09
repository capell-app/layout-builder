<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts;

use Illuminate\Database\Eloquent\Model;

interface WidgetAssetReferenceRepointer
{
    public function repoint(Model $asset, int|string $fromAssetId, int|string $toAssetId): int;
}
