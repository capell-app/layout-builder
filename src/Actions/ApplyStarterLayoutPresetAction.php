<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Actions\Mutations\NormalizeLayoutBuilderStateAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutMutationResultData;
use Capell\LayoutBuilder\Data\LayoutPresetData;
use Capell\LayoutBuilder\Support\LayoutPresets\LayoutPresetRegistry;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsObject;

final class ApplyStarterLayoutPresetAction
{
    use AsObject;

    public function __construct(
        private readonly LayoutPresetRegistry $presets,
        private readonly NormalizeLayoutBuilderStateAction $normalizeState,
    ) {}

    public function handle(string $presetKey): LayoutMutationResultData
    {
        $preset = $this->findPreset($presetKey);

        return $this->normalizeState->handle(new LayoutBuilderStateData(
            containers: $this->containersForPreset($preset),
            assets: [],
            originalAssets: [],
            selectedRecords: [],
        ));
    }

    private function findPreset(string $presetKey): LayoutPresetData
    {
        $presetKey = trim($presetKey);

        foreach ($this->presets->all() as $preset) {
            if ($preset->key === $presetKey) {
                return $preset;
            }
        }

        throw new InvalidArgumentException(sprintf('Unknown starter layout preset [%s].', $presetKey));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function containersForPreset(LayoutPresetData $preset): array
    {
        $containerKeys = $preset->containers !== [] ? array_values($preset->containers) : ['main'];
        $containers = [];

        foreach ($containerKeys as $containerKey) {
            $containers[$containerKey] = [
                'widgets' => [],
                'meta' => $this->containerMeta($containerKey, $preset),
            ];
        }

        $widgetOccurrences = [];

        foreach (array_values($preset->sections) as $sectionIndex => $widgetKey) {
            $targetContainerKey = $this->targetContainerKey($containerKeys, $sectionIndex);
            $widgetOccurrences[$widgetKey] = ($widgetOccurrences[$widgetKey] ?? 0) + 1;
            $containers[$targetContainerKey]['widgets'][] = [
                'widget_key' => $widgetKey,
                'occurrence' => $widgetOccurrences[$widgetKey],
                'meta' => [
                    'widget_settings' => [
                        'anchor_id' => Str::slug($widgetKey),
                    ],
                ],
            ];
        }

        return $containers;
    }

    /**
     * @param  array<int, string>  $containerKeys
     */
    private function targetContainerKey(array $containerKeys, int $sectionIndex): string
    {
        if (count($containerKeys) === 1) {
            return $containerKeys[0];
        }

        $lastContainerIndex = array_key_last($containerKeys);

        throw_if($lastContainerIndex === null, InvalidArgumentException::class, 'Starter layout presets require at least one container.');

        return $containerKeys[$sectionIndex] ?? $containerKeys[$lastContainerIndex];
    }

    /**
     * @return array<string, mixed>
     */
    private function containerMeta(string $containerKey, LayoutPresetData $preset): array
    {
        if ($preset->key !== 'sidebar-main-footer') {
            return ['colspan' => 12];
        }

        return match ($containerKey) {
            'sidebar' => ['colspan' => 4, 'responsive' => ['tablet' => ['colspan' => 12], 'mobile' => ['colspan' => 12]]],
            'main' => ['colspan' => 8, 'responsive' => ['tablet' => ['colspan' => 12], 'mobile' => ['colspan' => 12]]],
            default => ['colspan' => 12],
        };
    }
}
