<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Models\Block;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(Block $block, Layout $layout)
 */
class AddHeroBlockToLayoutAction
{
    use AsFake;
    use AsObject;

    public function handle(Block $block, Layout $layout, string $container = 'hero'): void
    {
        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        if (! array_key_exists($container, $containers)) {
            $containers = array_merge([$container => $this->heroContainer($block)], $containers);
        }

        $layout->update(['containers' => $containers]);

        AddBlockToLayoutContainerAction::run($block, $layout, $container, skipExists: true);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function heroContainer(Block $block): array
    {
        return [
            'meta' => [
                'colspan' => 12,
                'container' => 'full',
            ],
            'blocks' => [
                ['block_key' => $block->key],
            ],
        ];
    }
}
