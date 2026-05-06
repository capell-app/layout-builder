<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Database\Factories\LayoutFactory;
use Capell\LayoutBuilder\Enums\AssetEnum;
use Capell\LayoutBuilder\Enums\WidgetComponentEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Pest\Expectation;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('creates asset block widget with expected meta', function (): void {
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->blockWidget();
    WidgetAsset::factory()->count(3)->widget($widget)->asset(AssetEnum::Section)->create();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('assets-block')
        ->meta->scoped(
            fn (Expectation $meta) => $meta->component->toBe(WidgetComponentEnum::AssetBlock->value)
                ->component_item->toBe('capell-layout-builder::section.block'),
        )
        ->assets->toHaveCount(3);
});

it('renders asset block widget on page', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->blockWidget();
    WidgetAsset::factory()->count(3)->widget($widget)->asset(AssetEnum::Section)->create();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $widgetAssets = $widget->widgetAssets()
        ->ordered()
        ->alphabetical($language)
        ->with([
            'asset.type',
            'asset.translation',
        ])
        ->get();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-assets-blocks',
            fn (AssertElement $elm): BaseAssert => $elm->contains('.widget-block-item', count: 3)
                ->each(
                    '.widget-block-item',
                    fn (AssertElement $asset, int $index): BaseAssert => $asset->containsText($widgetAssets[$index]->asset->translation->title)
                        ->containsText(strip_tags((string) $widgetAssets[$index]->asset->translation->content)),
                ),
        );
});

it('empty asset block widget hidden', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->blockWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertDoesntExist('.widget-assets-block');
});

it('empty asset block widget visible', function (): void {
    config()->set('capell-layout-builder.widget.skip_render_empty', false);

    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->blockWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-assets-block');
});
