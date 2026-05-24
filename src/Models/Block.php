<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Models;

/**
 * @deprecated Use Widget. Kept for packages that still reference the legacy block model.
 */
class Block extends Widget
{
    public function getMorphClass(): string
    {
        return 'block';
    }
}
