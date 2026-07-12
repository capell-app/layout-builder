<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Capell\LayoutBuilder\Models\LayoutPreset;
use Spatie\LaravelData\Data;

final class LayoutPresetLinkData extends Data
{
    public function __construct(
        public readonly int $presetId,
        public readonly string $presetItemId,
        public readonly string $presetKey,
        public readonly bool $locked = true,
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function fromMeta(array $meta): ?self
    {
        $preset = $meta['preset'] ?? null;

        if (! is_array($preset)
            || ! is_numeric($preset['preset_id'] ?? null)
            || ! is_string($preset['preset_item_id'] ?? null)
            || trim($preset['preset_item_id']) === ''
            || ! is_string($preset['key'] ?? null)
            || trim($preset['key']) === ''
            || ($preset['locked'] ?? false) !== true) {
            return null;
        }

        return new self(
            presetId: (int) $preset['preset_id'],
            presetItemId: $preset['preset_item_id'],
            presetKey: $preset['key'],
        );
    }

    /** @return array<string, int|string|bool> */
    public function toArray(): array
    {
        return [
            'preset_id' => $this->presetId,
            'preset_item_id' => $this->presetItemId,
            'key' => $this->presetKey,
            'locked' => $this->locked,
        ];
    }

    public function matches(LayoutPreset $preset): bool
    {
        return $this->presetId === (int) $preset->getKey();
    }
}
