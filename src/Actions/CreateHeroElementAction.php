<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Enums\AssetEnum;
use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Enums\ElementComponentEnum;
use Capell\LayoutBuilder\Enums\ElementTypeEnum;
use Capell\LayoutBuilder\Enums\ElementTypeGroupEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Models\Element;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Element run(string $key = 'hero', ?string $label = null, string $height = '', array $meta = [])
 */
class CreateHeroElementAction
{
    use AsFake;
    use AsObject;

    public function handle(string $key = 'hero', ?string $label = null, string $height = '', array $meta = []): Element
    {
        /** @var class-string<Element> $elementModel */
        $elementModel = Element::class;

        return $elementModel::query()->updateOrCreate([
            'key' => $key,
        ], [
            'name' => $label ?? __('capell-layout-builder::generic.hero'),
            'blueprint_id' => $this->createType()->id,
            'meta' => [
                'component' => ElementComponentEnum::Hero,
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

    private function createType(): Blueprint
    {
        /** @var class-string<Blueprint> */
        $typeModel = Blueprint::class;

        return $typeModel::query()->firstOrCreate([
            'key' => ElementTypeEnum::Hero,
            'type' => LayoutTypeEnum::Element,
        ], [
            'name' => __('capell-layout-builder::generic.hero'),
            'group' => ElementTypeGroupEnum::Asset,
            'admin' => [
                'type_configurator' => 'Element',
                'configurator' => 'Assets',
                'icon' => 'heroicon-o-gift',
                'asset_types' => [
                    AssetEnum::Page,
                    'section',
                ],
            ],
            'meta' => [
                'component' => ElementComponentEnum::Assets,
                'additional_asset_relations' => [
                    'related.translation',
                    'related.pageUrl',
                ],
            ],
        ]);
    }
}
