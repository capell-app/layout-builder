<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderAssetHarness;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

/**
 * @return array{harness: LayoutBuilderAssetHarness, widget: Widget, page: Page, otherPage: Page, otherPageAsset: WidgetAsset}
 */
function pageScopedAssetFixture(): array
{
    $page = Page::factory()->withTranslations()->create();
    $otherPage = Page::factory()->withTranslations()->create();
    $widget = Widget::factory()->create(['key' => 'hero']);
    $otherPageAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($otherPage)
        ->create([
            'container' => 'main',
            'occurrence' => 1,
            'order' => 1,
            'workspace_id' => 0,
            'pageable_id' => $otherPage->getKey(),
            'pageable_type' => $otherPage->getMorphClass(),
        ]);

    $harness = new LayoutBuilderAssetHarness;
    $harness->layout = Layout::factory()->create();
    $harness->page = $page;
    $harness->containers = [
        'main' => [
            'widgets' => [
                ['widget_key' => $widget->key, 'occurrence' => 1],
            ],
        ],
    ];
    $harness->selectedRecords = ['main' => [[]]];
    $harness->setContainerWidgets(['main' => [$widget]]);

    return [
        'harness' => $harness,
        'widget' => $widget,
        'page' => $page,
        'otherPage' => $otherPage,
        'otherPageAsset' => $otherPageAsset,
    ];
}

it('does not re-home another page\'s widget asset when its id is submitted from this page', function (): void {
    ['harness' => $harness, 'page' => $page, 'otherPage' => $otherPage, 'otherPageAsset' => $otherPageAsset] = pageScopedAssetFixture();

    $harness->assets = [
        'main' => [
            [
                [
                    'id' => $otherPageAsset->getKey(),
                    'asset_type' => $page->getMorphClass(),
                    'asset_id' => $page->getKey(),
                    'order' => 0,
                    'occurrence' => 1,
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
            ],
        ],
    ];

    $harness->exposeUpdateAssets('main', 0);

    $otherPageAsset->refresh();

    expect($otherPageAsset->pageable_id)->toBe($otherPage->getKey())
        ->and((int) $otherPageAsset->asset_id)->toBe($otherPage->getKey())
        ->and(WidgetAsset::query()
            ->where('pageable_id', $page->getKey())
            ->where('asset_id', pageScopedAssetKey($page))
            ->exists())->toBeTrue();
});

it('does not delete another page\'s widget asset when its id is submitted from this page', function (): void {
    ['harness' => $harness, 'page' => $page, 'otherPageAsset' => $otherPageAsset] = pageScopedAssetFixture();

    $harness->assets = [
        'main' => [
            [
                [
                    'id' => $otherPageAsset->getKey(),
                    'asset_type' => 'not-the-same',
                    'asset_id' => 999999,
                    'order' => 0,
                    'occurrence' => 1,
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
            ],
        ],
    ];

    $harness->exposeUpdateAssets('main', 0);

    expect(WidgetAsset::query()->whereKey($otherPageAsset->getKey())->exists())->toBeTrue();
});

it('leaves another page\'s assets for the same widget occurrence alone when this page saves its own', function (): void {
    ['harness' => $harness, 'page' => $page, 'otherPage' => $otherPage, 'otherPageAsset' => $otherPageAsset] = pageScopedAssetFixture();

    $harness->assets = [
        'main' => [
            [
                [
                    'asset_type' => $page->getMorphClass(),
                    'asset_id' => $page->getKey(),
                    'order' => 0,
                    'occurrence' => 1,
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
            ],
        ],
    ];

    $harness->exposeUpdateAssets('main', 0);

    $otherPageAsset->refresh();

    expect($otherPageAsset->pageable_id)->toBe($otherPage->getKey())
        ->and(WidgetAsset::query()
            ->where('pageable_id', $page->getKey())
            ->where('asset_id', pageScopedAssetKey($page))
            ->exists())->toBeTrue();
});

function pageScopedAssetKey(Page $page): string
{
    $key = $page->getKey();
    throw_unless(is_int($key) || is_string($key), RuntimeException::class, 'Expected a scalar page key.');

    return (string) $key;
}
