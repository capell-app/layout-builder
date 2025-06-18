<?php

declare(strict_types=1);

namespace Capell\Layout\Observers;

use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Models\Widget;

class WidgetObserver
{
    public function creating(Widget $widget): void
    {
        if (! $widget->name && $widget->key) {
            $widget->name = str($widget->key)->title();
        }

        if (! $widget->key && $widget->name) {
            $widget->key = str($widget->name)->slug();
        }

        if (! $widget->type_id) {
            $widget->type_id = Type::query()->where('type', LayoutTypeEnum::Widget)->value('id');
        }
    }
}
