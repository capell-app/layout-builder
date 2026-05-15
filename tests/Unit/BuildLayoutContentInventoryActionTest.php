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
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

it('builds editor safe content groups in visual layout order from the package namespace', function (): void {
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => ['elements' => []],
            'footer' => ['elements' => []],
        ],
    ]);

    $mainElement = Element::factory()->create(['key' => 'featured-products', 'name' => 'Featured products']);
    $footerElement = Element::factory()->create(['key' => 'footer-links', 'name' => 'Footer links']);
    $sharedPage = Page::factory()->withTranslations()->create(['name' => 'Reusable product']);
    $footerPage = Page::factory()->withTranslations()->create(['name' => 'Terms page']);

    $mainAsset = ElementAsset::factory()
        ->element($mainElement)
        ->asset($sharedPage)
        ->container('main')
        ->occurrence(1)
        ->create(['order' => 1]);

    $reusedMainAsset = ElementAsset::factory()
        ->element($mainElement)
        ->asset($sharedPage)
        ->container('main')
        ->occurrence(1)
        ->create(['order' => 2]);

    $footerAsset = ElementAsset::factory()
        ->element($footerElement)
        ->asset($footerPage)
        ->container('footer')
        ->occurrence(1)
        ->create(['order' => 1]);

    $mainElement->setRelation('assets', new EloquentCollection([$mainAsset->load('asset.translation'), $reusedMainAsset->load('asset.translation')]));
    $footerElement->setRelation('assets', new EloquentCollection([$footerAsset->load('asset.translation')]));

    $inventory = BuildLayoutContentInventoryAction::run(
        layout: $layout,
        page: null,
        containers: [
            'main' => ['elements' => [['element_key' => $mainElement->key, 'occurrence' => 1]], 'meta' => []],
            'footer' => ['elements' => [['element_key' => $footerElement->key, 'occurrence' => 1]], 'meta' => []],
        ],
        containerElements: [
            'main' => [0 => $mainElement],
            'footer' => [0 => $footerElement],
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
            'elementIndex' => 0,
            'index' => 0,
            'type' => AssetEnum::Page->value,
            'contentInventorySignature' => 'known-signature',
        ])
        ->and($inventory->groups[0]->items[0]->key)->toBe('main:0:1:page:' . $sharedPage->getKey() . ':0')
        ->and($inventory->itemCount)->toBe(3);
});

it('lets higher priority package contributors decorate groups and items last', function (): void {
    $layout = Layout::factory()->create();
    $element = Element::factory()->create(['key' => 'hero', 'name' => 'Hero element']);
    $page = Page::factory()->withTranslations()->create(['name' => 'Home page']);
    $elementAsset = ElementAsset::factory()->element($element)->asset($page)->create();
    $element->setRelation('assets', new EloquentCollection([$elementAsset->load('asset.translation')]));

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
            return ['elements'];
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
        containers: ['hero' => ['elements' => [['element_key' => $element->key, 'occurrence' => 1]], 'meta' => []]],
        containerElements: ['hero' => [0 => $element]],
        assets: ['hero' => [0 => [layoutBuilderInventoryAssetState($elementAsset)]]],
        signature: 'known-signature',
        contributors: [$highPriorityContributor, $lowPriorityContributor],
    );

    expect($inventory->groups[0]->label)->toBe('Marketing hero')
        ->and($inventory->groups[0]->items[0]->label)->toBe('Primary hero card');
});

/**
 * @return array<string, mixed>
 */
function layoutBuilderInventoryAssetState(ElementAsset $elementAsset): array
{
    return [
        'id' => $elementAsset->getKey(),
        'layout_element_id' => $elementAsset->layout_element_id,
        'asset_id' => $elementAsset->asset_id,
        'asset_type' => $elementAsset->asset_type,
        'meta' => $elementAsset->meta ?? [],
        'order' => $elementAsset->order,
        'occurrence' => $elementAsset->occurrence,
    ];
}
