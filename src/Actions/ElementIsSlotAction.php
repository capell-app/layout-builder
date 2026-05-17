<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Models\Element;
use Lorisleiva\Actions\Concerns\AsObject;

final class ElementIsSlotAction
{
    use AsObject;

    public function handle(Element $element): bool
    {
        if (($element->meta['type'] ?? null) === 'slot') {
            return true;
        }

        if (! $element->relationLoaded('type')) {
            return false;
        }

        $type = $element->getRelation('type');

        return is_object($type) && method_exists($type, 'getMeta') && $type->getMeta('type') === 'slot';
    }
}
