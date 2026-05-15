<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Models\Element;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/**
 * @method static void run(Element $element, Layout $layout, string $container, bool $skipExists = false)
 */
class AddElementToLayoutContainerAction
{
    use AsObject;

    public function handle(Element $element, Layout $layout, string $container, bool $skipExists = false): void
    {
        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        throw_if(! isset($containers[$container]['elements']), RuntimeException::class, sprintf("Container '%s' not found in layout.", $container));

        $existingElements = array_filter(
            $containers[$container]['elements'],
            fn (array $existingElement): bool => $existingElement['element_key'] === $element->key,
        );

        if ($skipExists && $existingElements !== []) {
            return;
        }

        $occurrence = count($existingElements) + 1;

        $containers[$container]['elements'][] = [
            'element_key' => $element->key,
            'occurrence' => $occurrence,
        ];

        $layout->update(['containers' => $containers]);
    }
}
