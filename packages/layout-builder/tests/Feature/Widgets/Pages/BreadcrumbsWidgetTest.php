<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Database\Factories\LayoutFactory;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

test('breadcrumbs widget', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $widgetCreator = resolve(WidgetCreator::class);
    $widget = $widgetCreator->breadcrumbWidget();

    $layout = (new LayoutFactory)->widgets([$widget])->create();

    $home = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $parent = Page::factory()->site($site)->withTranslations()->create();
    $page = Page::factory()->site($site)->layout($layout)->parent($parent)->withTranslations()->create();

    $page->load('pageUrl', 'parent.pageUrl');

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            'nav.breadcrumbs',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($home->translation->label)
                ->containsText($parent->translation->title)
                ->containsText($page->translation->title),
        );
});

test('breadcrumbs are hidden on root page', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $widgetCreator = resolve(WidgetCreator::class);
    $widget = $widgetCreator->breadcrumbWidget();

    $layout = (new LayoutFactory)->widgets([$widget])->create();

    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertDoesntExist('nav.breadcrumbs');
});
