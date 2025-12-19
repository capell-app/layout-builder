<?php

declare(strict_types=1);

use Capell\Admin\Enums\LayoutEnum;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Services\Creator\LayoutCreator;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

uses(TestingFrontend::class);

test('child pages are listed on a page', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $layoutCreator = app(LayoutCreator::class);
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

todo('test breadcrumbs are shown on a page full hierarchy');
