<?php

declare(strict_types=1);

use Capell\Core\Enums\AssetEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Widget;
use Capell\Core\Models\WidgetAsset;
use Capell\LayoutBuilder\Actions\BuildLayoutContentInventoryAction;
use Capell\LayoutBuilder\Contracts\LayoutContentGroupContributor;
use Capell\LayoutBuilder\Data\LayoutContentGroupData;
use Capell\LayoutBuilder\Data\LayoutContentInventoryContextData;
use Capell\LayoutBuilder\Data\LayoutContentItemData;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

it('builds editor safe content groups in visual layout order from the package namespace', function (): void {
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => ['widgets' => []],
            'footer' => ['widgets' => []],
        ],
    ]);

    $mainWidget = Widget::factory()->create(['key' => 'featured-products', 'name' => 'Featured products']);
    $footerWidget = Widget::factory()->create(['key' => 'footer-links', 'name' => 'Footer links']);
    $sharedPage = Page::factory()->withTranslations()->create(['name' => 'Reusable product']);
    $footerPage = Page::factory()->withTranslations()->create(['name' => 'Terms page']);

    $mainAsset = WidgetAsset::factory()
        ->widget($mainWidget)
        ->asset($sharedPage)
        ->container('main')
        ->occurrence(1)
        ->create(['order' => 1]);

    $reusedMainAsset = WidgetAsset::factory()
        ->widget($mainWidget)
        ->asset($sharedPage)
        ->container('main')
        ->occurrence(1)
        ->create(['order' => 2]);

    $footerAsset = WidgetAsset::factory()
        ->widget($footerWidget)
        ->asset($footerPage)
        ->container('footer')
        ->occurrence(1)
        ->create(['order' => 1]);

    $mainWidget->setRelation('assets', new EloquentCollection([$mainAsset->load('asset.translation'), $reusedMainAsset->load('asset.translation')]));
    $footerWidget->setRelation('assets', new EloquentCollection([$footerAsset->load('asset.translation')]));

    $inventory = BuildLayoutContentInventoryAction::run(
        layout: $layout,
        page: null,
        containers: [
            'main' => ['widgets' => [['widget_key' => $mainWidget->key, 'occurrence' => 1]], 'meta' => []],
            'footer' => ['widgets' => [['widget_key' => $footerWidget->key, 'occurrence' => 1]], 'meta' => []],
        ],
        containerWidgets: [
            'main' => [0 => $mainWidget],
            'footer' => [0 => $footerWidget],
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
            'widgetIndex' => 0,
            'index' => 0,
            'type' => AssetEnum::Page->value,
            'contentInventorySignature' => 'known-signature',
        ])
        ->and($inventory->groups[0]->items[0]->key)->toBe('main:0:1:page:' . $sharedPage->getKey() . ':0')
        ->and($inventory->itemCount)->toBe(3);
});

it('lets higher priority package contributors decorate groups and items last', function (): void {
    $layout = Layout::factory()->create();
    $widget = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero widget']);
    $page = Page::factory()->withTranslations()->create(['name' => 'Home page']);
    $widgetAsset = WidgetAsset::factory()->widget($widget)->asset($page)->create();
    $widget->setRelation('assets', new EloquentCollection([$widgetAsset->load('asset.translation')]));

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
            return ['widgets'];
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
        containers: ['hero' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]], 'meta' => []]],
        containerWidgets: ['hero' => [0 => $widget]],
        assets: ['hero' => [0 => [layoutBuilderInventoryAssetState($widgetAsset)]]],
        signature: 'known-signature',
        contributors: [$highPriorityContributor, $lowPriorityContributor],
    );

    expect($inventory->groups[0]->label)->toBe('Marketing hero')
        ->and($inventory->groups[0]->items[0]->label)->toBe('Primary hero card');
});

/**
 * @return array<string, mixed>
 */
function layoutBuilderInventoryAssetState(WidgetAsset $widgetAsset): array
{
    return [
        'id' => $widgetAsset->getKey(),
        'widget_id' => $widgetAsset->widget_id,
        'asset_id' => $widgetAsset->asset_id,
        'asset_type' => $widgetAsset->asset_type,
        'meta' => $widgetAsset->meta ?? [],
        'order' => $widgetAsset->order,
        'occurrence' => $widgetAsset->occurrence,
    ];
}
