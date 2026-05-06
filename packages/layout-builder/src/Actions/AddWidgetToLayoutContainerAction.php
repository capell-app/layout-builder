<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Models\Widget;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/**
 * @method static void run(Widget $widget, Layout $layout, string $container, bool $skipExists = false)
 */
class AddWidgetToLayoutContainerAction
{
    use AsObject;

    public function handle(Widget $widget, Layout $layout, string $container, bool $skipExists = false): void
    {
        throw_if(! isset($layout->containers[$container]['widgets']), RuntimeException::class, sprintf("Container '%s' not found in layout.", $container));

        $containers = $layout->containers;

        $existingWidgets = array_filter(
            $containers[$container]['widgets'],
            fn (array $existingWidget): bool => $existingWidget['widget_key'] === $widget->key,
        );

        if ($skipExists && $existingWidgets !== []) {
            return;
        }

        $occurrence = count($existingWidgets) + 1;

        $containers[$container]['widgets'][] = [
            'widget_key' => $widget->key,
            'occurrence' => $occurrence,
        ];

        $layout->update(['containers' => $containers]);
    }
}
