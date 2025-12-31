<?php

declare(strict_types=1);

use Capell\Admin\Enums\LayoutEnum;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Services\Creator\LayoutCreator;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;

uses(TestingFrontend::class);

test('child pages are listed on a page', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $layoutCreator = resolve(LayoutCreator::class);
    $layout = $layoutCreator->createWithContainers(LayoutEnum::Default->value, createWidgets: true);

    $parent = Page::factory()->site($site)->withTranslations()->create();
    $page = Page::factory()->site($site)->layout($layout)->parent($parent)->withTranslations()->children(2)->create();

    $page->load('children.translation', 'pageUrl');

    expect($page)->children->toHaveCount(2);

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertSeeText($page->translation->title)
        ->assertSeeInOrder($page->children->pluck('translation.title')->toArray());
});

it('shows breadcrumbs', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $layoutCreator = resolve(LayoutCreator::class);
    $layout = $layoutCreator->createWithContainers(LayoutEnum::Default->value, createWidgets: true);

    $home = Page::factory()->site($site)->home()->withTranslations()->create();
    $parent = Page::factory()->site($site)->withTranslations()->create();
    $page = Page::factory()->site($site)->layout($layout)->parent($parent)->withTranslations()->create();

    $page->load('pageUrl', 'parent.pageUrl');

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            'nav.breadcrumbs',
            fn (AssertElement $elm) => $elm->containsText($home->translation->label)
                ->containsText($parent->translation->title)
                ->containsText($page->translation->title),
        );
});
