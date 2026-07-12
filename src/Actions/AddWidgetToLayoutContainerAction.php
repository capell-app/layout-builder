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
        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        $containerData = $containers[$container] ?? null;
        throw_if(! is_array($containerData) || ! isset($containerData['widgets']) || ! is_array($containerData['widgets']), RuntimeException::class, sprintf("Container '%s' not found in layout.", $container));

        $widgets = $containerData['widgets'];

        $existingWidgets = array_filter(
            $widgets,
            fn (mixed $existingWidget): bool => is_array($existingWidget) && ($existingWidget['widget_key'] ?? null) === $widget->key,
        );

        if ($skipExists && $existingWidgets !== []) {
            return;
        }

        $occurrence = count($existingWidgets) + 1;

        $widgets[] = [
            'widget_key' => $widget->key,
            'occurrence' => $occurrence,
        ];
        $containerData['widgets'] = $widgets;
        $containers[$container] = $containerData;

        $layout->update(['containers' => $containers]);
    }
}
