<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\LayoutBuilder\Data\LayoutFragmentData;

final class LayoutPresetRepository
{
    private const SESSION_KEY = 'capell.layout-builder.presets';

    public function put(string $name, string $description, LayoutFragmentData $fragment): void
    {
        $presets = $this->all();
        $presets[$name] = [
            'description' => $description,
            'fragment' => $fragment,
        ];

        session()->put(self::SESSION_KEY, $presets);
    }

    public function find(string $name): ?LayoutFragmentData
    {
        $preset = $this->all()[$name] ?? null;

        if (! is_array($preset)) {
            return null;
        }

        $fragment = $preset['fragment'] ?? null;

        return $fragment instanceof LayoutFragmentData ? $fragment : null;
    }

    /**
     * @return array<string, array{description: string, fragment: LayoutFragmentData}>
     */
    public function all(): array
    {
        $presets = session()->get(self::SESSION_KEY);

        return is_array($presets) ? $presets : [];
    }
}
