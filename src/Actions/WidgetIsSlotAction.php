<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Models\Widget;
use Lorisleiva\Actions\Concerns\AsObject;

final class WidgetIsSlotAction
{
    use AsObject;

    public function handle(Widget $widget): bool
    {
        if (($widget->meta['type'] ?? null) === 'slot') {
            return true;
        }

        if (! $widget->relationLoaded('blueprint')) {
            return false;
        }

        $type = $widget->getRelation('blueprint');

        return is_object($type) && method_exists($type, 'getMeta') && $type->getMeta('type') === 'slot';
    }
}
