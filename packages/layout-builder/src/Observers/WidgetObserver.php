<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Observers;

use Capell\Core\Actions\GenerateUniqueKeyAction;
use Capell\Core\Models\Type;
use Capell\LayoutBuilder\Actions\InvalidateWidgetLayoutPreviewImagesAction;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Models\Widget;
use InvalidArgumentException;

class WidgetObserver
{
    public function creating(Widget $widget): void
    {
        if ($widget->name === null && $widget->key !== null && $widget->key !== '') {
            $widget->name = str($widget->key)->title();
        }

        if ($widget->key === null || $widget->key === '') {
            $widget->key = GenerateUniqueKeyAction::run($widget);
        }

        if ($widget->type_id === null) {
            $widget->type_id = Type::query()->where('type', LayoutTypeEnum::Widget)->default()->value('id');
            throw_if($widget->type_id === null, InvalidArgumentException::class, 'Unable to create widget without a type.');
        }
    }

    public function updated(Widget $widget): void
    {
        if (! $widget->wasChanged(['key', 'name', 'admin', 'type_id'])) {
            return;
        }

        InvalidateWidgetLayoutPreviewImagesAction::run([
            $widget->getOriginal('key'),
            $widget->key,
        ]);
    }
}
