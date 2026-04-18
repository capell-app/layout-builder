<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Actions\GetPageVariablesAction;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

test('children widget', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations()->create();

    $widgetCreator = resolve(WidgetCreator::class);
    $widget = $widgetCreator->childrenWidget();

    $layout = (new LayoutFactory)->widgets([$widget])->create();

    $parent = Page::factory()->site($site)->withTranslations($language)->create();
    $page = Page::factory()->site($site)->layout($layout)->parent($parent)->withTranslations($language)->children(2)->create();

    $page->load([
        'children' => fn (BuilderContract $query) => $query->alphabetical($language)
            ->with([
                'translation',
                'pageUrl.siteDomain',
            ]),
    ]);

    $children = $page->children;

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-children',
            fn (AssertElement $elm): BaseAssert => $elm->find(
                '.widget-content',
                fn (AssertElement $elm): BaseAssert => $elm->containsText(__($widget->translation->title, GetPageVariablesAction::run($page))),
            )
                ->contains('.children-page-item', 2)
                ->each(
                    '.children-page-item',
                    fn (AssertElement $asset, int $index): BaseAssert => $asset->containsText($children[$index]->translation->title)
                        ->find(
                            'a',
                            fn (AssertElement $aElm): BaseAssert => $aElm->has(
                                'href',
                                $children[$index]->pageUrl->full_url,
                            ),
                        ),
                ),
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
