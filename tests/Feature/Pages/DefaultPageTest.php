<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Creator\LayoutCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

test('home page with layout', function (): void {
    $theme = Theme::factory()->create();
    $site = Site::factory()->theme($theme)->withTranslations()->create();

    $layoutCreator = resolve(LayoutCreator::class);
    $layout = $layoutCreator->create(LayoutEnum::Default);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            'title',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($page->translation->title . ' | ' . $site->title),
        )
        ->assertElementExists(
            'h1',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($page->translation->title),
        )
        ->assertElementExists(
            '.widget-page-content',
            fn (AssertElement $elm): BaseAssert => $elm->containsText(strip_tags((string) $page->translation->content)),
        );
});
