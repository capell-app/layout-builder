<?php

declare(strict_types=1);

use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Services\Creator\WidgetCreator;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('renders siblings widget on page', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $languages = CapellCore::getModel(CoreModelEnum::Language)::query()->get();
    $widget = $creator->siblingsWidget(null, $languages);
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $parent = Page::factory()->site($site)->withTranslations()->create();
    $pages = Page::factory()->count(3)->site($site)->parent($parent)->layout($layout)->withTranslations()->create();
    $page = $pages->random();
    $siblings = $pages->where('id', '!=', $page->id)->values();
    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-siblings',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($widget->translation->title)
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
