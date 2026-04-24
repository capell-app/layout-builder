<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Mosaic\Database\Factories\LayoutFactory;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Support\Creator\WidgetCreator;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Illuminate\Database\Eloquent\Relations\Relation;
use Pest\Expectation;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('creates navigation widget with expected meta', function (): void {
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->navigationWidget();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('widget-navigation')
        ->meta->scoped(fn (Expectation $meta) => $meta->navigation->toBe('navigation'));
});

it('renders navigation widget on page', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $creator = resolve(WidgetCreator::class);
    $layout = (new LayoutFactory)->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $home = Page::factory()->site($site)->home()->withTranslations(languages: $language, slug: '/')->create();
    $services = Page::factory()
        ->site($site)
        ->withTranslations()
        ->children(3)
        ->state(['name' => 'Support'])
        ->create();
    $services->load([
        'translation',
        'children' => fn (Relation $query) => $query->ordered()->alphabetical($language)->with(['translation', 'pageUrl.siteDomain']),
    ]);

    $anotherSiteHome = Page::factory()->home()->withTranslations(slug: '/')->create();
    $externalUrl = 'https://example.com/external';
    $items = [
        [
            'label' => 'Home',
            'type' => NavigationItemType::Page->value,
            'data' => [
                'pageable_id' => $home->getKey(),
                'pageable_type' => $home->getMorphClass(),
            ],
        ],
        [
            'label' => $page->translation->title,
            'type' => NavigationItemType::Page->value,
            'data' => [
                'pageable_id' => $page->getKey(),
                'pageable_type' => $page->getMorphClass(),
            ],
        ],
        [
            'label' => 'Another Site',
            'type' => NavigationItemType::Page->value,
            'data' => [
                'pageable_id' => $anotherSiteHome->getKey(),
                'pageable_type' => $anotherSiteHome->getMorphClass(),
            ],
        ],
        [
            'label' => 'External link',
            'type' => NavigationItemType::Link->value,
            'data' => [
                'url' => $externalUrl,
            ],
        ],
        [
            'label' => $services->translation->title,
            'type' => NavigationItemType::Page->value,
            'data' => [
                'pageable_id' => $services->getKey(),
                'pageable_type' => $services->getMorphClass(),
                'auto_children' => true,
            ],
        ],
    ];

    $widget = $creator->navigationWidget(site: $site, navigationItems: $items);

    $layout->update([
        'containers' => [
            'main' => [
                'widgets' => [
                    [
                        'widget_key' => $widget->key,
                        'occurrence' => 1,
                    ],
                ],
                'meta' => [],
            ],
        ],
    ]);

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-navigation',
            fn (AssertElement $element): BaseAssert => $element->contains('.widget-navigation-item', 8)
                ->find(
                    '.list-items',
                    fn (AssertElement $el): BaseAssert => $el->find(
                        '.widget-navigation-item:nth-child(1)',
                        fn (AssertElement $el): BaseAssert => $el->containsText('Home')
                            ->find('a', fn (AssertElement $link): BaseAssert => $link->has('href', $home->pageUrl->full_url)),
                    )
                        ->find(
                            '.widget-navigation-item:nth-child(2)',
                            fn (AssertElement $el): BaseAssert => $el->containsText($page->translation->title)
                                ->has('class', 'active')
                                ->find('a', fn (AssertElement $link): BaseAssert => $link->has('href', $page->pageUrl->full_url)),
                        )
                        ->find(
                            '.widget-navigation-item:nth-child(3)',
                            fn (AssertElement $el): BaseAssert => $el->containsText('Another Site')
                                ->find('a', fn (AssertElement $link): BaseAssert => $link->has('href', $anotherSiteHome->pageUrl->full_url)),
                        )
                        ->find(
                            '.widget-navigation-item:nth-child(4)',
                            fn (AssertElement $el): BaseAssert => $el->containsText('External link')
                                ->find('a', fn (AssertElement $link): BaseAssert => $link->has('href', $externalUrl)),
                        )
                        ->find(
                            '.widget-navigation-item:nth-child(5)',
                            fn (AssertElement $el): BaseAssert => $el->containsText($services->translation->title)
                                ->find('a', fn (AssertElement $link): BaseAssert => $link->has('href', $services->pageUrl->full_url))
                                ->find(
                                    '.list-items',
                                    fn (AssertElement $el): BaseAssert => $el->contains('.widget-navigation-item', 3)
                                        ->each(
                                            '.widget-navigation-item:nth-child(1)',
                                            fn (AssertElement $el, int $index): BaseAssert => $el->containsText($services->children->get($index)->translation->title)
                                                ->find('a', fn (AssertElement $link): BaseAssert => $link->has('href', $services->children->get($index)->pageUrl->full_url)),
                                        ),
                                ),
                        ),
                ),
        );
});

it('empty navigation widget hidden', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->navigationWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertDoesntExist('.widget-navigation');
});

it('empty navigation widget visible', function (): void {
    config()->set('capell-mosaic.widget.skip_render_empty', false);

    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->navigationWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-navigation');
});
