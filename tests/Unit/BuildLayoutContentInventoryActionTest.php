<?php

declare(strict_types=1);

use Capell\Core\Enums\AssetEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Actions\BuildLayoutContentInventoryAction;
use Capell\LayoutBuilder\Contracts\LayoutContentGroupContributor;
use Capell\LayoutBuilder\Data\LayoutContentGroupData;
use Capell\LayoutBuilder\Data\LayoutContentInventoryContextData;
use Capell\LayoutBuilder\Data\LayoutContentItemData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
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

    expect($inventory->groups)->toHaveCount(1)
        ->and($inventory->groups[0]->label)->toBe(__('capell-layout-builder::generic.page_content_sources'))
        ->and($inventory->groups[0]->items[0]->label)->toBe('Reusable product')
        ->and($inventory->groups[0]->items[0]->sourceLabel)->toBe(__('capell-layout-builder::generic.page_translation_source'))
        ->and($inventory->groups[0]->items[0]->sourceDetail)->toBe(__('capell-layout-builder::generic.content_tab_title_content_fields'))
        ->and($inventory->groups[0]->items[0]->canEditAsset)->toBeTrue()
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

it('adds widget copy as its own editable ownership group', function (): void {
    $layout = Layout::factory()->create();
    $language = Language::factory()->create();
    $widget = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero widget']);
    $page = Page::factory()->withTranslations()->create(['name' => 'Home page']);
    $widgetAsset = WidgetAsset::factory()->widget($widget)->asset($page)->create();

    $widgetTranslation = Translation::query()->create([
        'translatable_type' => $widget->getMorphClass(),
        'translatable_id' => $widget->getKey(),
        'language_id' => $language->getKey(),
        'title' => 'Every section can be rebuilt in the layout builder',
        'content' => '<p>Own this line on the widget, not the attached section.</p>',
    ]);

    $widget->setRelation('translation', $widgetTranslation);
    $widget->setRelation('assets', new EloquentCollection([$widgetAsset->load('asset.translation')]));

    $inventory = BuildLayoutContentInventoryAction::run(
        layout: $layout,
        page: null,
        containers: ['main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]], 'meta' => []]],
        containerWidgets: ['main' => [0 => $widget]],
        assets: ['main' => [0 => [layoutBuilderInventoryAssetState($widgetAsset)]]],
        signature: 'known-signature',
    );

    expect($inventory->groups)->toHaveCount(2)
        ->and($inventory->groups[0]->key)->toBe('widget-content')
        ->and($inventory->groups[0]->items[0]->canEditAsset)->toBeFalse()
        ->and($inventory->groups[0]->items[0]->hasWidgetCopySource)->toBeTrue()
        ->and($inventory->groups[0]->items[0]->sourceLabel)->toBe(__('capell-layout-builder::generic.widget_translation_source'))
        ->and($inventory->groups[0]->items[0]->renderedText)->toContain('Every section can be rebuilt in the layout builder')
        ->and($inventory->groups[0]->items[0]->widgetEditActionArguments)->toMatchArray([
            'containerKey' => 'main',
            'widgetIndex' => 0,
        ])
        ->and($inventory->groups[1]->key)->toBe('page-content')
        ->and($inventory->groups[1]->items[0]->warnings)->toContain(__('capell-layout-builder::message.widget_copy_source_warning'))
        ->and($inventory->itemCount)->toBe(2);
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
