<?php

declare(strict_types=1);

namespace Capell\Hero\Actions;

use Capell\Core\Enums\AssetEnum;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Type;
use Capell\Hero\Enums\WidgetComponentEnum;
use Capell\Hero\Enums\WidgetTypeEnum;
use Capell\Hero\Filament\Resources\Widgets\Schemas\Types\HeroWidgetSchema;
use Capell\Layout\Enums\AssetEnum as LayoutAssetEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Enums\WidgetTypeGroupEnum;
use Capell\Layout\Filament\Resources\Types\Schemas\Types\WidgetTypeSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\AssetsWidgetSchema;
use Capell\Layout\Models\Widget;
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
        $widgetModel = CapellCore::getModel(ModelEnum::Widget->name);

        return $widgetModel::query()->updateOrCreate([
            'key' => $key,
        ], [
            'name' => $label ?? __('capell-hero::generic.hero'),
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
                'schema' => HeroWidgetSchema::getKey(),
                'asset_types' => [LayoutAssetEnum::Content->value],
            ],
        ]);
    }

    private function createType(): Type
    {
        /** @var class-string<Type> */
        $typeModel = CapellCore::getModel(CoreModelEnum::Type->name);

        return $typeModel::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Hero,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-hero::generic.hero'),
            'group' => WidgetTypeGroupEnum::Asset,
            'admin' => [
                'type_schema' => WidgetTypeSchema::getKey(),
                'schema' => AssetsWidgetSchema::getKey(),
                'icon' => 'heroicon-o-gift',
                'asset_types' => [
                    AssetEnum::Page,
                    LayoutAssetEnum::Content,
                ],
            ],
            'meta' => [
                'component' => \Capell\Layout\Enums\WidgetComponentEnum::Assets,
                'additional_asset_relations' => [
                    'related.translation',
                    'related.pageUrl',
                ],
            ],
        ]);
    }
}
