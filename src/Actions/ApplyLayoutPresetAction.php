<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Illuminate\Support\Str;
use LogicException;
use Lorisleiva\Actions\Concerns\AsObject;

final class ApplyLayoutPresetAction
{
    use AsObject;

    public function handle(LayoutPreset $preset, Layout $layout, Site $site, bool $persist = false): Layout
    {
        if ($preset->site_id !== $site->getKey() || ($layout->site_id !== null && (int) $layout->site_id !== (int) $site->getKey())) {
            throw new LogicException('Layout presets can only be applied within the same site.');
        }

        $snapshot = is_array($preset->snapshot) ? $preset->snapshot : [];
        $containers = is_array($snapshot['containers'] ?? null) ? $snapshot['containers'] : [];
        $containers = app(SaveLayoutPresetAction::class)->sanitizePresetContainers($containers);

        $layout->setAttribute('containers', $this->withUniqueAnchors($containers));

        if ($persist) {
            $layout->save();

            return $layout->refresh();
        }

        return $layout;
    }

    /**
     * @param  array<string, mixed>  $containers
     * @return array<string, mixed>
     */
    private function withUniqueAnchors(array $containers): array
    {
        $usedAnchors = [];

        foreach ($containers as &$container) {
            if (! is_array($container)) {
                continue;
            }

            $elements = is_array($container['elements'] ?? null) ? $container['elements'] : [];

            foreach ($elements as &$element) {
                if (! is_array($element)) {
                    continue;
                }

                $anchor = $element['meta']['block_settings']['anchor_id'] ?? null;
                if (! is_string($anchor) || trim($anchor) === '') {
                    continue;
                }

                $baseAnchor = Str::slug($anchor);
                $uniqueAnchor = $baseAnchor;
                $suffix = 2;

                while (isset($usedAnchors[$uniqueAnchor])) {
                    $uniqueAnchor = $baseAnchor . '-' . $suffix;
                    $suffix++;
                }

                $element['meta']['block_settings']['anchor_id'] = $uniqueAnchor;
                $usedAnchors[$uniqueAnchor] = true;
            }

            $container['elements'] = $elements;
        }

        return $containers;
    }
}
