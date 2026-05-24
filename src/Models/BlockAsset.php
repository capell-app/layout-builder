<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Models;

/**
 * @deprecated Use WidgetAsset. Kept for packages that still reference the legacy block asset model.
 */
class BlockAsset extends WidgetAsset
{
    public function getMorphClass(): string
    {
        return 'block_asset';
    }
}
