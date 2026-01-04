<?php

declare(strict_types=1);

use Capell\Admin\Enums\LayoutEnum;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Services\Creator\LayoutCreator;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

test('home page with layout', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $layoutCreator = resolve(LayoutCreator::class);
    $layout = $layoutCreator->createWithContainers(LayoutEnum::Default->value, createWidgets: true);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertSeeHtml('<title>' . e($page->translation->title) . ' | ' . e($site->title) . '</title>')
        ->assertElementExists(
            'h1',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($page->translation->title),
        )
        ->assertElementExists(
            '.widget-page-content',
            fn (AssertElement $elm): BaseAssert => $elm->containsText(strip_tags((string) $page->translation->content)),
        );
});

todo('test children and other widgets on default page');
