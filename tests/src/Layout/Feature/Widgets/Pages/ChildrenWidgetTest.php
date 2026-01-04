<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Services\Creator\WidgetCreator;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

test('children widget', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $widgetCreator = resolve(WidgetCreator::class);
    $widget = $widgetCreator->childrenWidget();

    $layout = (new LayoutFactory)->widgets([$widget])->create();

    $parent = Page::factory()->site($site)->withTranslations()->create();
    $page = Page::factory()->site($site)->layout($layout)->parent($parent)->withTranslations()->children(2)->create();

    $page->load('children.translation', 'pageUrl');

    expect($page)->children->toHaveCount(2);

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-children',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($page->children[0]->translation->title)
                ->containsText($page->children[1]->translation->title),
        );
});

test('children widget is hidden', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $widgetCreator = resolve(WidgetCreator::class);
    $widget = $widgetCreator->childrenWidget();

    $layout = (new LayoutFactory)->widgets([$widget])->create();

    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertDoesntExist('.widget-children');
});
