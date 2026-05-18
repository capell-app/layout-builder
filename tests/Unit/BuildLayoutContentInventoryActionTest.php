<?php

declare(strict_types=1);

use Capell\Core\Enums\AssetEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Actions\BuildLayoutContentInventoryAction;
use Capell\LayoutBuilder\Contracts\LayoutContentGroupContributor;
use Capell\LayoutBuilder\Data\LayoutContentGroupData;
use Capell\LayoutBuilder\Data\LayoutContentInventoryContextData;
use Capell\LayoutBuilder\Data\LayoutContentItemData;
use Capell\LayoutBuilder\Models\Block;
use Capell\LayoutBuilder\Models\BlockAsset;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

it('builds editor safe content groups in visual layout order from the package namespace', function (): void {
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => ['blocks' => []],
            'footer' => ['blocks' => []],
        ],
    ]);

    $mainBlock = Block::factory()->create(['key' => 'featured-products', 'name' => 'Featured products']);
    $footerBlock = Block::factory()->create(['key' => 'footer-links', 'name' => 'Footer links']);
    $sharedPage = Page::factory()->withTranslations()->create(['name' => 'Reusable product']);
    $footerPage = Page::factory()->withTranslations()->create(['name' => 'Terms page']);

    $mainAsset = BlockAsset::factory()
        ->block($mainBlock)
        ->asset($sharedPage)
        ->container('main')
        ->occurrence(1)
        ->create(['order' => 1]);

    $reusedMainAsset = BlockAsset::factory()
        ->block($mainBlock)
        ->asset($sharedPage)
        ->container('main')
        ->occurrence(1)
        ->create(['order' => 2]);

    $footerAsset = BlockAsset::factory()
        ->block($footerBlock)
        ->asset($footerPage)
        ->container('footer')
        ->occurrence(1)
        ->create(['order' => 1]);

    $mainBlock->setRelation('assets', new EloquentCollection([$mainAsset->load('asset.translation'), $reusedMainAsset->load('asset.translation')]));
    $footerBlock->setRelation('assets', new EloquentCollection([$footerAsset->load('asset.translation')]));

    $inventory = BuildLayoutContentInventoryAction::run(
        layout: $layout,
        page: null,
        containers: [
            'main' => ['blocks' => [['block_key' => $mainBlock->key, 'occurrence' => 1]], 'meta' => []],
            'footer' => ['blocks' => [['block_key' => $footerBlock->key, 'occurrence' => 1]], 'meta' => []],
        ],
        containerBlocks: [
            'main' => [0 => $mainBlock],
            'footer' => [0 => $footerBlock],
        ],
        assets: [
            'main' => [
                0 => [
                    layoutBuilderInventoryAssetState($mainAsset),
                    layoutBuilderInventoryAssetState($reusedMainAsset),
                ],
            ],
            'footer' => [
                0 => [
                    layoutBuilderInventoryAssetState($footerAsset),
                ],
            ],
        ],
        signature: 'known-signature',
    );

    expect($inventory->groups)->toHaveCount(2)
        ->and($inventory->groups[0]->label)->toBe('Main content')
        ->and($inventory->groups[1]->label)->toBe('Footer')
        ->and($inventory->groups[0]->items[0]->label)->toBe('Reusable product')
        ->and($inventory->groups[0]->items[0]->isReused)->toBeTrue()
        ->and($inventory->groups[0]->items[0]->editActionArguments)->toMatchArray([
            'containerKey' => 'main',
            'blockIndex' => 0,
            'index' => 0,
            'type' => AssetEnum::Page->value,
            'contentInventorySignature' => 'known-signature',
        ])
        ->and($inventory->groups[0]->items[0]->key)->toBe('main:0:1:page:' . $sharedPage->getKey() . ':0')
        ->and($inventory->itemCount)->toBe(3);
});

it('lets higher priority package contributors decorate groups and items last', function (): void {
    $layout = Layout::factory()->create();
    $block = Block::factory()->create(['key' => 'hero', 'name' => 'Hero block']);
    $page = Page::factory()->withTranslations()->create(['name' => 'Home page']);
    $blockAsset = BlockAsset::factory()->block($block)->asset($page)->create();
    $block->setRelation('assets', new EloquentCollection([$blockAsset->load('asset.translation')]));

    $lowPriorityContributor = new class implements LayoutContentGroupContributor
    {
        public function priority(): int
        {
            return 1;
        }

        public function group(LayoutContentGroupData $group, LayoutContentInventoryContextData $context): LayoutContentGroupData
        {
            $group->label = 'Low priority hero';

            return $group;
        }

        public function item(LayoutContentItemData $item, LayoutContentInventoryContextData $context): LayoutContentItemData
        {
            $item->label = 'Low priority card';

            return $item;
        }

        public function eagerLoads(): array
        {
            return ['asset'];
        }

        public function cacheDependencies(): array
        {
            return ['blocks'];
        }
    };

    $highPriorityContributor = new class implements LayoutContentGroupContributor
    {
        public function priority(): int
        {
            return 10;
        }

        public function group(LayoutContentGroupData $group, LayoutContentInventoryContextData $context): LayoutContentGroupData
        {
            $group->label = 'Marketing hero';

            return $group;
        }

        public function item(LayoutContentItemData $item, LayoutContentInventoryContextData $context): LayoutContentItemData
        {
            $item->label = 'Primary hero card';

            return $item;
        }

        public function eagerLoads(): array
        {
            return ['asset.translation'];
        }

        public function cacheDependencies(): array
        {
            return ['pages'];
        }
    };

    $inventory = BuildLayoutContentInventoryAction::run(
        layout: $layout,
        page: null,
        containers: ['hero' => ['blocks' => [['block_key' => $block->key, 'occurrence' => 1]], 'meta' => []]],
        containerBlocks: ['hero' => [0 => $block]],
        assets: ['hero' => [0 => [layoutBuilderInventoryAssetState($blockAsset)]]],
        signature: 'known-signature',
        contributors: [$highPriorityContributor, $lowPriorityContributor],
    );

    expect($inventory->groups[0]->label)->toBe('Marketing hero')
        ->and($inventory->groups[0]->items[0]->label)->toBe('Primary hero card');
});

/**
 * @return array<string, mixed>
 */
function layoutBuilderInventoryAssetState(BlockAsset $blockAsset): array
{
    return [
        'id' => $blockAsset->getKey(),
        'block_id' => $blockAsset->block_id,
        'asset_id' => $blockAsset->asset_id,
        'asset_type' => $blockAsset->asset_type,
        'meta' => $blockAsset->meta ?? [],
        'order' => $blockAsset->order,
        'occurrence' => $blockAsset->occurrence,
    ];
}
