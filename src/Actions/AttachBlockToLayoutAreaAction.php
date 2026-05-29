<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
use Capell\LayoutBuilder\Support\LayoutBlockData;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Layout run(Layout $layout, string $area, string $widgetKey, ?string $containerKey = null, int $occurrence = 1, array<array-key, mixed> $containerMeta = [], ?string $containerName = null)
 */
final class AttachBlockToLayoutAreaAction
{
    use AsObject;

    public function __construct(private readonly LayoutAreaRegistry $areas) {}

    /**
     * @param  array<string, mixed>  $containerMeta
     */
    public function handle(
        Layout $layout,
        string $area,
        string $widgetKey,
        ?string $containerKey = null,
        int $occurrence = 1,
        array $containerMeta = [],
        ?string $containerName = null,
    ): Layout {
        $normalizedWidgetKey = trim($widgetKey);
        throw_if($normalizedWidgetKey === '', InvalidArgumentException::class, 'Widget key cannot be empty.');

        $normalizedArea = $this->areas->normalizeAreaKey($area);
        $normalizedContainerKey = $containerKey !== null && trim($containerKey) !== ''
            ? str(trim($containerKey))->slug()->toString()
            : $normalizedArea;
        $normalizedOccurrence = max(1, $occurrence);

        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        $container = $containers[$normalizedContainerKey] ?? [];
        $container = is_array($container) ? $container : [];

        $blocks = LayoutBlockData::fromContainer($container);
        $hasBlock = collect($blocks)
            ->contains(static fn (array $layoutBlock): bool => LayoutBlockData::key($layoutBlock) === $normalizedWidgetKey
                && LayoutBlockData::occurrence($layoutBlock) === $normalizedOccurrence);

        if (! $hasBlock) {
            $blocks[] = [
                'widget_key' => $normalizedWidgetKey,
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
            'widgets' => $blocks,
        ];

        $layout->update(['containers' => $containers]);

        return $layout->refresh();
    }
}
