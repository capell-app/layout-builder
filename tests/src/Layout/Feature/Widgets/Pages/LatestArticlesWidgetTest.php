<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

test('latest pages widget', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $widgetCreator = resolve(WidgetCreator::class);
    $widget = $widgetCreator->latestPagesWidget();

    $layout = (new LayoutFactory)->widgets([$widget])->create();

    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    $latestPages = Page::factory()->count(3)->site($site)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-latest-pages',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($latestPages[0]->translation->title)
                ->containsText($latestPages[1]->translation->title)
                ->containsText($latestPages[2]->translation->title),
        );
});

test('latest pages widget not visible', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $widgetCreator = resolve(WidgetCreator::class);
    $widget = $widgetCreator->latestPagesWidget();

    $layout = (new LayoutFactory)->widgets([$widget])->create();

    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertDoesntExist('.widget-latest-pages');
});

test('empty latest pages widget visible', function (): void {
    config()->set('capell-layout.widget.skip_render_empty', false);

    $site = Site::factory()->withTranslations()->create();

    $widgetCreator = resolve(WidgetCreator::class);
    $widget = $widgetCreator->latestPagesWidget();

    $layout = (new LayoutFactory)->widgets([$widget])->create();

    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-latest-pages',
            fn (AssertElement $elm): BaseAssert => $elm->containsText('No pages found.'),
        );
});
