<?php

declare(strict_types=1);

use Capell\Core\Enums\AssetEnum;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Capell\Layout\Services\Creator\WidgetCreator;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

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
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->pagesCardWidget();
    WidgetAsset::factory()->count(3)->widget($widget)->asset(AssetEnum::Page)->create();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();
    $widgetAssets = $widget->widgetAssets()->ordered()->with(['asset.translation', 'asset.type'])->get();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-pages-card',
            fn (AssertElement $elm): BaseAssert => $elm->contains('.widget-asset', 3)
                ->each(
                    '.widget-asset',
                    fn (AssertElement $asset, int $index): BaseAssert => $asset->containsText($widgetAssets[$index]->asset->translation->title)
                        ->containsText(strip_tags((string) $widgetAssets[$index]->asset->translation->content)),
                ),
        );
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
    config()->set('capell-layout.widget.skip_render_empty', false);

    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->pagesCardWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-pages-card');
});
