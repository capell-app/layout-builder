<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Enums\LayoutPresetMode;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Lorisleiva\Actions\Concerns\AsObject;

final class UpdateLinkedLayoutPresetItemAction
{
    use AsObject;

    /**
     * @param  array<string, mixed>  $container
     */
    public function handle(LayoutPreset $preset, string $presetItemId, array $container): LayoutPreset
    {
        return DB::transaction(function () use ($preset, $presetItemId, $container): LayoutPreset {
            $lockedPreset = LayoutPreset::query()->lockForUpdate()->find($preset->getKey());
            throw_unless($lockedPreset instanceof LayoutPreset, LogicException::class, 'The linked layout preset no longer exists.');
            throw_unless($lockedPreset->mode === LayoutPresetMode::Linked, LogicException::class, 'Only linked layout presets may be updated through linked containers.');

            $snapshot = is_array($lockedPreset->snapshot) ? $lockedPreset->snapshot : [];
            $items = is_array($snapshot['items'] ?? null) ? $snapshot['items'] : [];
            $updated = false;

            foreach ($items as $index => $item) {
                if (! is_array($item) || ($item['id'] ?? null) !== $presetItemId) {
                    continue;
                }

                $items[$index]['container'] = resolve(SaveLayoutPresetAction::class)->sanitizeLinkedPresetContainer($container);
                $updated = true;
                break;
            }

            throw_unless($updated, InvalidArgumentException::class, 'The linked layout preset item no longer exists.');

            $snapshot['items'] = array_values($items);
            $lockedPreset->forceFill([
                'snapshot' => $snapshot,
                'revision' => $lockedPreset->revision + 1,
            ])->save();

            return $lockedPreset->refresh();
        });
    }
}
