<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Models\Block;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/**
 * @method static void run(Block $block, Layout $layout, string $container, bool $skipExists = false)
 */
class AddBlockToLayoutContainerAction
{
    use AsObject;

    public function handle(Block $block, Layout $layout, string $container, bool $skipExists = false): void
    {
        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        throw_if(! isset($containers[$container]['blocks']), RuntimeException::class, sprintf("Container '%s' not found in layout.", $container));

        $existingBlocks = array_filter(
            $containers[$container]['blocks'],
            fn (array $existingBlock): bool => $existingBlock['block_key'] === $block->key,
        );

        if ($skipExists && $existingBlocks !== []) {
            return;
        }

        $occurrence = count($existingBlocks) + 1;

        $containers[$container]['blocks'][] = [
            'block_key' => $block->key,
            'occurrence' => $occurrence,
        ];

        $layout->update(['containers' => $containers]);
    }
}
