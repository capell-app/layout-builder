<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Models\Widget;
use Capell\Layout\Services\Creator\WidgetCreator;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;
use Pest\Expectation;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('creates navigation tabs widget with expected meta', function (): void {
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->navigationTabsWidget();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('widget-navigation-tabs')
        ->meta->scoped(
            fn (Expectation $meta) => $meta->navigation->toBe('navigation-tabs')
                ->view_file->toBe('capell-layout::components.widget.navigation.tabs'),
        );
});

it('renders navigation tabs widget on page', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $home = Page::factory()->site($site)->home()->withTranslations()->create();
    $items = [
        [
            'label' => $home->translation->title,
            'type' => 'page',
            'data' => [
                'page_id' => $home->id,
            ],
        ],
        [
            'label' => 'External link',
            'type' => 'external_url',
            'data' => [
                'url' => $externalUrl = 'https://example.com/external',
            ],
        ],
    ];
    $widget = $creator->navigationTabsWidget(navigatonItems: $items);
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-navigation-tabs',
            fn (AssertElement $element): BaseAssert => $element->contains('.tab-item', 2)
                ->find(
                    '.tab-items',
                    fn (AssertElement $el): BaseAssert => $el->find(
                        '.tab-item:nth-child(1)',
                        fn (AssertElement $el): BaseAssert => $el->containsText($home->translation->title)
                            ->find('a', fn (AssertElement $link): BaseAssert => $link->has('href', $home->pageUrl->full_url)),
                    )
                        ->find(
                            '.tab-item:nth-child(2)',
                            fn (AssertElement $el): BaseAssert => $el->containsText('External link')
                                ->find('a', fn (AssertElement $link): BaseAssert => $link->has('href', $externalUrl)),
                        ),
                ),
        );
});

it('empty navigation tabs widget hidden', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->navigationTabsWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertDoesntExist('.widget-navigation-tabs');
});

it('empty navigation tabs widget visible', function (): void {
    config()->set('capell-layout.widget.skip_render_empty', false);

    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->navigationTabsWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-navigation-tabs');
});
