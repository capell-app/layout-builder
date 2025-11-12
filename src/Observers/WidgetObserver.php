<?php

declare(strict_types=1);

namespace Capell\Layout\Observers;

use Capell\Core\Actions\GenerateUniqueKeyAction;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Models\Widget;
use InvalidArgumentException;

class WidgetObserver
{
    public function creating(Widget $widget): void
    {
        if (! $widget->name && $widget->key) {
            $widget->name = str($widget->key)->title();
        }

        if (! $widget->key) {
            $widget->key = GenerateUniqueKeyAction::run($widget);
        }

        if (! $widget->type_id) {
            $widget->type_id = Type::query()->where('type', LayoutTypeEnum::Widget)->default()->value('id');
            throw_unless($widget->type_id, InvalidArgumentException::class, 'Unable to create widget without a type.');
        }
    }
}
