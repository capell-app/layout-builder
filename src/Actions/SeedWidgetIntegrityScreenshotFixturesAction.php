<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use LogicException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run()
 */
final class SeedWidgetIntegrityScreenshotFixturesAction
{
    use AsFake;
    use AsObject;

    public const string UNUSED_WIDGET_KEY = 'screenshot-unused-disabled-widget';

    public const string ASSET_TARGET_WIDGET_KEY = 'screenshot-widget-asset-target';

    public const string BROKEN_ASSET_CONTAINER = 'screenshot-broken-widget-asset';

    public const string UNSCOPED_ASSET_CONTAINER = 'screenshot-unscoped-widget-asset';

    public function handle(): void
    {
        $widgetType = resolve(TypeCreator::class)->defaultWidgetType();

        $this->upsertWidget(self::UNUSED_WIDGET_KEY, [
            'name' => 'Screenshot unused and disabled widget',
            'blueprint_id' => $widgetType->getKey(),
            'component' => 'capell.layout-builder.widget.default',
            'is_livewire' => false,
            'status' => false,
            'meta' => [
                'component' => 'capell.layout-builder.widget.default',
            ],
        ]);

        $assetTarget = $this->upsertWidget(self::ASSET_TARGET_WIDGET_KEY, [
            'name' => 'Screenshot widget asset target',
            'blueprint_id' => $widgetType->getKey(),
            'component' => 'capell.layout-builder.widget.default',
            'is_livewire' => false,
            'status' => true,
            'meta' => [
                'component' => 'capell.layout-builder.widget.default',
            ],
        ]);

        $this->upsertWidgetAsset([
            'widget_id' => $this->widgetId($assetTarget),
            'container' => self::BROKEN_ASSET_CONTAINER,
            'occurrence' => 1,
        ], [
            'asset_id' => 999999999,
            'asset_type' => (string) $assetTarget->getMorphClass(),
            'order' => 1,
            'pageable_id' => null,
            'pageable_type' => null,
        ]);

        $this->upsertWidgetAsset([
            'widget_id' => $this->widgetId($assetTarget),
            'container' => self::UNSCOPED_ASSET_CONTAINER,
            'occurrence' => 1,
        ], [
            'asset_id' => $this->widgetId($assetTarget),
            'asset_type' => (string) $assetTarget->getMorphClass(),
            'order' => 2,
            'pageable_id' => null,
            'pageable_type' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertWidget(string $key, array $attributes): Widget
    {
        $widget = Widget::query()->firstOrNew(['key' => $key]);
        $widget->fill($attributes);

        if (! $widget->exists || $widget->isDirty()) {
            $widget->save();
        }

        return $widget;
    }

    /**
     * @param  array<string, int|string>  $identity
     * @param  array<string, int|string|null>  $attributes
     */
    private function upsertWidgetAsset(array $identity, array $attributes): void
    {
        $widgetAsset = WidgetAsset::query()->firstOrNew($identity);
        $widgetAsset->fill($attributes);

        if (! $widgetAsset->exists || $widgetAsset->isDirty()) {
            $widgetAsset->save();
        }
    }

    private function widgetId(Widget $widget): int
    {
        $id = $widget->getKey();

        throw_unless(is_int($id), LogicException::class, 'Screenshot fixture widgets must use integer identifiers.');

        return $id;
    }
}
