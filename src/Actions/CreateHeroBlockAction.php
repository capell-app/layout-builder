<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Enums\AssetEnum;
use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Enums\BlockComponentEnum;
use Capell\LayoutBuilder\Enums\BlockTypeEnum;
use Capell\LayoutBuilder\Enums\BlockTypeGroupEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Models\Block;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Block run(string $key = 'hero', ?string $label = null, string $height = '', array $meta = [])
 */
class CreateHeroBlockAction
{
    use AsFake;
    use AsObject;

    public function handle(string $key = 'hero', ?string $label = null, string $height = '', array $meta = []): Block
    {
        /** @var class-string<Block> $blockModel */
        $blockModel = Block::class;

        return $blockModel::query()->updateOrCreate([
            'key' => $key,
        ], [
            'name' => $label ?? __('capell-layout-builder::generic.hero'),
            'blueprint_id' => $this->createType()->id,
            'meta' => [
                'component' => BlockComponentEnum::Hero->value,
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
            'key' => BlockTypeEnum::Hero,
            'type' => LayoutTypeEnum::Block,
        ], [
            'name' => __('capell-layout-builder::generic.hero'),
            'group' => BlockTypeGroupEnum::Asset,
            'admin' => [
                'type_configurator' => 'Block',
                'configurator' => 'Assets',
                'icon' => 'heroicon-o-gift',
                'asset_types' => [
                    AssetEnum::Page,
                    'section',
                ],
            ],
            'meta' => [
                'component' => BlockComponentEnum::Assets,
                'additional_asset_relations' => [
                    'related.translation',
                    'related.pageUrl',
                ],
            ],
        ]);
    }
}
