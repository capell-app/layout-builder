<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Database\Factories\LayoutFactory;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('creates assets widget with expected meta', function (): void {
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->assetsWidget();
    WidgetAsset::factory()->count(3)->widget($widget)->create();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('assets')
        ->assets->toHaveCount(3);
});

it('renders assets widget on page', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->assetsWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $widgetAssets = WidgetAsset::factory()->count(3)->widget($widget)->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-assets',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($widgetAssets[0]->asset->translation->title)
                ->containsText($widgetAssets[1]->asset->translation->title)
                ->containsText($widgetAssets[2]->asset->translation->title),
        );
});

it('empty assets widget hidden', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->assetsWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertDoesntExist('.widget-assets');
});

it('empty assets widget visible', function (): void {
    config()->set('capell-layout-builder.widget.skip_render_empty', false);

    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->assetsWidget();
    WidgetAsset::factory()->count(3)->widget($widget)->create();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-assets');
});
