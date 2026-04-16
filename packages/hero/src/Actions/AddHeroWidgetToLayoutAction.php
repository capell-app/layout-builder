<?php

declare(strict_types=1);

namespace Capell\Hero\Actions;

use Capell\Core\Models\Layout;
use Capell\Layout\Actions\AddWidgetToLayoutContainerAction;
use Capell\Layout\Models\Widget;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(Widget $widget, Layout $layout)
 */
class AddHeroWidgetToLayoutAction
{
    use AsFake;
    use AsObject;

    public function handle(Widget $widget, Layout $layout, string $container = 'hero'): void
    {
        $containers = $layout->containers ?? [];

        if (! array_key_exists($container, $containers)) {
            $containers = array_merge([$container => $this->heroContainer($widget)], $containers);
        }

        $layout->update(['containers' => $containers]);

        AddWidgetToLayoutContainerAction::run($widget, $layout, $container);
    }

    private function heroContainer(Widget $widget): array
    {
        return [
            'meta' => [
                'colspan' => 12,
                'container' => 'full',
            ],
            'widgets' => [
                ['widget_key' => $widget->key],
            ],
        ];
    }
}
