<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
use Capell\LayoutBuilder\Support\LayoutElementData;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Layout run(Layout $layout, string $area, string $elementKey, ?string $containerKey = null, int $occurrence = 1, array $containerMeta = [], ?string $containerName = null)
 */
final class AttachElementToLayoutAreaAction
{
    use AsObject;

    public function __construct(private readonly LayoutAreaRegistry $areas) {}

    /**
     * @param  array<string, mixed>  $containerMeta
     */
    public function handle(
        Layout $layout,
        string $area,
        string $elementKey,
        ?string $containerKey = null,
        int $occurrence = 1,
        array $containerMeta = [],
        ?string $containerName = null,
    ): Layout {
        $normalizedElementKey = trim($elementKey);
        throw_if($normalizedElementKey === '', InvalidArgumentException::class, 'Element key cannot be empty.');

        $normalizedArea = $this->areas->normalizeAreaKey($area);
        $normalizedContainerKey = $containerKey !== null && trim($containerKey) !== ''
            ? str(trim($containerKey))->slug()->toString()
            : $normalizedArea;
        $normalizedOccurrence = max(1, $occurrence);

        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        $container = $containers[$normalizedContainerKey] ?? [];
        $container = is_array($container) ? $container : [];

        $elements = LayoutElementData::normalizeMany($container['elements'] ?? []);
        $hasElement = collect($elements)
            ->contains(static fn (array $layoutElement): bool => LayoutElementData::key($layoutElement) === $normalizedElementKey
                && LayoutElementData::occurrence($layoutElement) === $normalizedOccurrence);

        if (! $hasElement) {
            $elements[] = [
                'element_key' => $normalizedElementKey,
                'occurrence' => $normalizedOccurrence,
            ];
        }

        $meta = isset($container['meta']) && is_array($container['meta'])
            ? $container['meta']
            : [];

        $containers[$normalizedContainerKey] = [
            ...$container,
            'name' => $container['name'] ?? $containerName ?? str($normalizedArea)->headline()->toString(),
            'meta' => [
                ...$meta,
                ...$containerMeta,
                'area' => $normalizedArea,
            ],
            'elements' => $elements,
        ];

        $layout->update(['containers' => $containers]);

        return $layout->refresh();
    }
}
