<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Models\Widget;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/**
 * @method static void run(Widget $block, Layout $layout, string $container, bool $skipExists = false)
 */
class AddBlockToLayoutContainerAction
{
    use AsObject;

    public function handle(Widget $block, Layout $layout, string $container, bool $skipExists = false): void
    {
        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        throw_if(! isset($containers[$container]['widgets']), RuntimeException::class, sprintf("Container '%s' not found in layout.", $container));

        $existingBlocks = array_filter(
            $containers[$container]['widgets'],
            fn (array $existingBlock): bool => $existingBlock['widget_key'] === $block->key,
        );

        if ($skipExists && $existingBlocks !== []) {
            return;
        }

        $occurrence = count($existingBlocks) + 1;

        $containers[$container]['widgets'][] = [
            'widget_key' => $block->key,
            'occurrence' => $occurrence,
        ];

        $layout->update(['containers' => $containers]);
    }
}
