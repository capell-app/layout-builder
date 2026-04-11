<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Frontend\Actions\GetLayoutContainerWidthAction;
use Capell\Layout\Enums\ContainerWidthEnum;
use Capell\Layout\Models\Widget;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static ContainerWidthEnum run(Widget $widget, ?string $default = null)
 */
class GetWidgetContainerWidthAction
{
    use AsObject;

    public function handle(Widget $widget, ?string $default = null): ContainerWidthEnum
    {
        if ($containerWidth = $widget->getMeta('container')) {
            return ContainerWidthEnum::from($containerWidth);
        }

        return GetLayoutContainerWidthAction::run($default);
    }
}
