<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Models\Widget;
use Lorisleiva\Actions\Concerns\AsObject;

final class BlockIsSlotAction
{
    use AsObject;

    public function handle(Widget $block): bool
    {
        if (($block->meta['type'] ?? null) === 'slot') {
            return true;
        }

        if (! $block->relationLoaded('type')) {
            return false;
        }

        $type = $block->getRelation('type');

        return is_object($type) && method_exists($type, 'getMeta') && $type->getMeta('type') === 'slot';
    }
}
