<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Core\Models\Layout;
use Capell\Layout\Models\Widget;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/**
 * @method static int run(Widget $widget, Layout $layout, string $container)
 */
class AddWidgetToLayoutContainerAction
{
    use AsObject;

    public function handle(Widget $widget, Layout $layout, string $container): int
    {
        if (empty($layout->containers[$container]['widgets'])) {
            throw new RuntimeException(sprintf("Container '%s' not found in layout.", $container));
        }

        $containers = $layout->containers;

        $occurrence = count(array_filter(
            $containers[$container]['widgets'],
            fn ($existingWidget): bool => $existingWidget['widget_key'] === $widget->key
        )) + 1;

        $containers[$container]['widgets'][] = [
            'widget_key' => $widget->key,
            'occurrence' => $occurrence,
        ];

        $layout->update(['containers' => $containers]);

        return $occurrence;
    }
}
