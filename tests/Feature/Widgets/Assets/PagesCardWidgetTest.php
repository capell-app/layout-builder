<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Mosaic\Database\Factories\LayoutFactory;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Models\WidgetAsset;
use Capell\Mosaic\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('creates pages card widget with expected defaults', function (): void {
    $creator = resolve(WidgetCreator::class);

    $widget = $creator->pagesCardWidget();

    expect($widget)->toBeInstanceOf(Widget::class)
        ->and($widget->key)->toBe('pages-card')
        ->and($widget->meta['limit'] ?? null)->toBe(10)
        ->and($widget->meta['with_image'] ?? null)->toBeTrue();
});

it('renders pages card widget on page', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->pagesCardWidget();
    $assetPages = Page::factory()->count(3)->site($site)->withTranslations($language)->create();
    $assetPages->each(function (Page $assetPage) use ($widget): void {
        WidgetAsset::factory()->widget($widget)->asset($assetPage)->create();
    });
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $response = get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-pages-card',
            fn (AssertElement $elm): BaseAssert => $elm->contains('.pages-card-page-item', 3),
        );

    foreach ($assetPages as $assetPage) {
        $response->assertSee($assetPage->translation->title, false);
    }
});

it('empty pages card widget hidden', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->pagesCardWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertDoesntExist('.widget-pages-card');
});

it('empty pages card widget visible', function (): void {
    config()->set('capell-mosaic.widget.skip_render_empty', false);

    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->pagesCardWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-pages-card');
});
