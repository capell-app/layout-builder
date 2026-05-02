<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Actions\GetPageVariablesAction;
use Capell\Mosaic\Database\Factories\LayoutFactory;
use Capell\Mosaic\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('renders siblings widget on page', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);

    $widget = $creator->siblingsWidget(null, collect([$language]));
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $parent = Page::factory()->site($site)->withTranslations()->create();
    $pages = Page::factory()->count(3)->site($site)->parent($parent)->layout($layout)->withTranslations($language)->create();
    $page = $pages->random();
    $siblings = $pages
        ->where('id', '!=', $page->id)
        ->sortBy(static fn (Page $siblingPage): string => $siblingPage->translation->title)
        ->values();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-siblings',
            fn (AssertElement $elm): BaseAssert => $elm->containsText(__($widget->translation->title, GetPageVariablesAction::run($page)))
                ->contains('.siblings-page-item', 2)
                ->each(
                    '.siblings-page-item',
                    fn (AssertElement $asset, int $index): BaseAssert => $asset->containsText($siblings[$index]->translation->title)
                        ->find(
                            'a',
                            fn (AssertElement $aElm): BaseAssert => $aElm->has(
                                'href',
                                $siblings[$index]->pageUrl->full_url,
                            ),
                        ),
                ),
        );
});
