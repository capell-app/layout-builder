<?php

declare(strict_types=1);

namespace Capell\Mosaic\Actions;

use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Frontend\Actions\GetLayoutContainerWidthAction;
use Capell\Mosaic\Models\Widget;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static ContainerWidthEnum run(Widget $widget, ?string $default = null)
 */
class GetWidgetContainerWidthAction
{
    use AsObject;

    public function handle(Widget $widget, ?string $default = null): ContainerWidthEnum
    {
        $containerWidth = $widget->getMeta('container');

        if ($containerWidth !== null) {
            return ContainerWidthEnum::from($containerWidth);
        }

        return GetLayoutContainerWidthAction::run($default);
    }
}
