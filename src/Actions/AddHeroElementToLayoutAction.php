<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Models\Element;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(Element $element, Layout $layout)
 */
class AddHeroElementToLayoutAction
{
    use AsFake;
    use AsObject;

    public function handle(Element $element, Layout $layout, string $container = 'hero'): void
    {
        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        if (! array_key_exists($container, $containers)) {
            $containers = array_merge([$container => $this->heroContainer($element)], $containers);
        }

        $layout->update(['containers' => $containers]);

        AddElementToLayoutContainerAction::run($element, $layout, $container, skipExists: true);
    }

    private function heroContainer(Element $element): array
    {
        return [
            'meta' => [
                'colspan' => 12,
                'container' => 'full',
            ],
            'elements' => [
                ['element_key' => $element->key],
            ],
        ];
    }
}
