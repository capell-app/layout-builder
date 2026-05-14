<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Enums\AssetEnum;
use Capell\Core\Models\Type;
use Capell\Core\Models\Widget;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Enums\WidgetComponentEnum;
use Capell\LayoutBuilder\Enums\WidgetTypeEnum;
use Capell\LayoutBuilder\Enums\WidgetTypeGroupEnum;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Widget run(string $key = 'hero', ?string $label = null, string $height = '', array $meta = [])
 */
class CreateHeroWidgetAction
{
    use AsFake;
    use AsObject;

    public function handle(string $key = 'hero', ?string $label = null, string $height = '', array $meta = []): Widget
    {
        /** @var class-string<Widget> $widgetModel */
        $widgetModel = Widget::class;

        return $widgetModel::query()->updateOrCreate([
            'key' => $key,
        ], [
            'name' => $label ?? __('capell-layout-builder::generic.hero'),
            'type_id' => $this->createType()->id,
            'meta' => [
                'component' => WidgetComponentEnum::Hero,
                'heading_size' => 'h1',
                'height' => $height,
                'carousel_fade' => true,
                'carousel_arrows' => false,
                'carousel_pagination' => true,
                'carousel_loop' => true,
                'carousel_auto_play' => true,
                'carousel_auto_delay' => 50000,
                'color' => 'dark',
                'extra_relations' => [
                    'assets.asset.translation',
                ],
                ...$meta,
            ],
            'admin' => [
                'icon' => 'heroicon-o-gift',
                'configurator' => 'Hero',
                'asset_types' => ['section'],
            ],
        ]);
    }

    private function createType(): Type
    {
        /** @var class-string<Type> */
        $typeModel = Type::class;

        return $typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Hero,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-layout-builder::generic.hero'),
            'group' => WidgetTypeGroupEnum::Asset,
            'admin' => [
                'type_configurator' => 'Widget',
                'configurator' => 'Assets',
                'icon' => 'heroicon-o-gift',
                'asset_types' => [
                    AssetEnum::Page,
                    'section',
                ],
            ],
            'meta' => [
                'component' => WidgetComponentEnum::Assets,
                'additional_asset_relations' => [
                    'related.translation',
                    'related.pageUrl',
                ],
            ],
        ]);
    }
}
