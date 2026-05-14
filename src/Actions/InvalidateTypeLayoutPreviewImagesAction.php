<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Type;
use Capell\Core\Models\Widget;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static int run(Type $type)
 */
class InvalidateTypeLayoutPreviewImagesAction
{
    use AsObject;

    public function handle(Type $type): int
    {
        $widgetKeys = Widget::query()
            ->where('blueprint_id', $type->getKey())
            ->pluck('key')
            ->all();

        return InvalidateWidgetLayoutPreviewImagesAction::run($widgetKeys);
    }
}
