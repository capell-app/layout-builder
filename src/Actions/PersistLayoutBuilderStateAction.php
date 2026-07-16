<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Data\LayoutPresetLinkData;
use Capell\LayoutBuilder\Enums\LayoutPresetMode;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class PersistLayoutBuilderStateAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  array<array-key, mixed>  $containers
     */
    public function handle(Layout $layout, ?Model $page, array $containers, Closure $persistWidgetAssets, ?CarbonImmutable $expectedUpdatedAt = null, ?LayoutPreset $linkedPreset = null, ?LayoutPresetLinkData $linkedPresetLink = null, ?string $linkedContainerKey = null): Layout
    {
        DB::transaction(function () use ($layout, $page, $containers, $persistWidgetAssets, $expectedUpdatedAt, $linkedPreset, $linkedPresetLink, $linkedContainerKey): void {
            $lockedLayout = Layout::query()->lockForUpdate()->find($layout->getKey());
            throw_unless($lockedLayout instanceof Layout, LogicException::class, 'The layout no longer exists.');

            if ($expectedUpdatedAt !== null && $lockedLayout->updated_at?->format('Y-m-d H:i:s.u') !== $expectedUpdatedAt->format('Y-m-d H:i:s.u')) {
                throw new LogicException('The layout changed in another editor. Reload before saving.');
            }

            $layout->setRawAttributes($lockedLayout->getAttributes(), true);
            $layout->setRelations($lockedLayout->getRelations());
            $layout->update([
                'containers' => $containers,
            ]);

            if ($page instanceof Model && $page->getAttribute('layout_id') !== $layout->getKey()) {
                $page->update([
                    'layout_id' => $layout->getKey(),
                ]);
            }

            $persistWidgetAssets();

            if (! $linkedPreset instanceof LayoutPreset || ! $linkedPresetLink instanceof LayoutPresetLinkData) {
                return;
            }

            throw_unless(is_string($linkedContainerKey) && isset($containers[$linkedContainerKey]) && is_array($containers[$linkedContainerKey]), LogicException::class, 'The linked source container is no longer present.');

            $lockedPreset = LayoutPreset::query()->lockForUpdate()->find($linkedPreset->getKey());
            throw_unless($lockedPreset instanceof LayoutPreset && $lockedPreset->mode === LayoutPresetMode::Linked, LogicException::class, 'The linked layout preset no longer exists.');

            $snapshot = is_array($lockedPreset->snapshot) ? $lockedPreset->snapshot : [];
            $items = is_array($snapshot['items'] ?? null) ? $snapshot['items'] : [];
            $updated = false;

            foreach ($items as $itemIndex => $item) {
                if (! is_array($item) || ($item['id'] ?? null) !== $linkedPresetLink->presetItemId) {
                    continue;
                }

                $linkedContainer = $containers[$linkedContainerKey];
                /** @var array<string, mixed> $linkedContainer */
                $item = $items[$itemIndex] ?? null;
                if (! is_array($item)) {
                    continue;
                }
                $item['container'] = resolve(SaveLayoutPresetAction::class)->sanitizeLinkedPresetContainer($linkedContainer);
                $items[$itemIndex] = $item;
                $updated = true;
                break;
            }

            throw_unless($updated, LogicException::class, 'The linked preset item no longer exists.');

            $snapshot['items'] = array_values($items);
            $lockedPreset->forceFill([
                'snapshot' => $snapshot,
                'revision' => $lockedPreset->revision + 1,
            ])->save();
        });

        InvalidateLayoutPreviewImageAction::run($layout);
        $freshLayout = $layout->fresh();
        throw_unless($freshLayout instanceof Layout, LogicException::class, 'The layout no longer exists.');
        SyncLayoutPresetUsagesAction::run($freshLayout);

        return $freshLayout;
    }
}
