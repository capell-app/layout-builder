<?php

declare(strict_types=1);

use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Capell\LayoutBuilder\Database\Factories\LayoutFactory;
use Capell\LayoutBuilder\Enums\ActionLinkEnum;
use Capell\LayoutBuilder\Enums\WidgetComponentEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('creates default widget with expected meta', function (): void {
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->defaultWidget();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('default');
});

it('renders default widget on page', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->defaultWidget();
    $widgetTranslation = Translation::factory()->translatable($widget)->language($site->language)->create();
    $image = Media::factory()->model($widget)->image()->create();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-default',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($widgetTranslation->title)
                ->containsText(strip_tags((string) $widgetTranslation->content))
                ->find(
                    'img',
                    fn (AssertElement $imgElm): BaseAssert => $imgElm->has('alt', $image->name)
                        ->has('src', $image->getFullUrl()),
                ),
        );
});

it('does not render widgets before their visible from date', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = Widget::factory()->create([
        'visible_from' => now()->addDay(),
    ]);
    Translation::factory()->translatable($widget)->language($site->language)->create([
        'title' => 'Future announcement',
    ]);
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertDontSee('Future announcement');
});

it('does not render widgets after their visible until date', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = Widget::factory()->create([
        'visible_until' => now()->subDay(),
    ]);
    Translation::factory()->translatable($widget)->language($site->language)->create([
        'title' => 'Expired announcement',
    ]);
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertDontSee('Expired announcement');
});

it('renders widgets inside their schedule window', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = Widget::factory()->create([
        'visible_from' => now()->subDay(),
        'visible_until' => now()->addDay(),
    ]);
    Translation::factory()->translatable($widget)->language($site->language)->create([
        'title' => 'Current announcement',
    ]);
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertSee('Current announcement');
});

it('renders announcement and snippet widgets', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $announcement = Widget::factory()->create([
        'key' => 'announcement-render-test',
        'meta' => ['component' => WidgetComponentEnum::AnnouncementBar->value],
    ]);
    $snippet = Widget::factory()->create([
        'key' => 'snippet-render-test',
        'meta' => ['component' => WidgetComponentEnum::Snippet->value],
    ]);

    Translation::factory()->translatable($announcement)->language($site->language)->create([
        'title' => 'Launch window',
        'content' => '<p>Early access opens today.</p>',
    ]);
    Translation::factory()->translatable($snippet)->language($site->language)->create([
        'title' => 'Small note',
        'content' => '<p>Useful supporting copy.</p>',
    ]);

    $layout = (new LayoutFactory)->widgets([$announcement, $snippet])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-announcement-bar')
        ->assertElementExists('.widget-snippet')
        ->assertSee('Launch window')
        ->assertSee('Small note');
});

it('renders chrome container keys through the normal layout container path', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = Widget::factory()->create([
        'key' => 'chrome-banner-render-test',
        'meta' => ['component' => WidgetComponentEnum::AnnouncementBar->value],
    ]);
    Translation::factory()->translatable($widget)->language($site->language)->create([
        'title' => 'Chrome announcement',
    ]);
    $layout = (new LayoutFactory)->create([
        'containers' => [
            'chrome-top' => [
                'widgets' => [
                    [
                        'widget_key' => $widget->key,
                        'occurrence' => 1,
                    ],
                ],
                'meta' => [],
            ],
        ],
        'widgets' => [$widget->key],
    ]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('#layout-container-chrome-top')
        ->assertSee('Chrome announcement');
});

it('renders default actions widget on page', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $page = Page::factory()->site($site)->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->defaultWidget();
    $meta = $widget->meta;
    $meta['actions'] = [
        [
            'type' => ActionLinkEnum::Link->value,
            'url' => 'https://example.com',
            'label' => 'External',
            'hide_label' => true,
            'icon' => 'heroicon-o-arrow-top-right-on-square',
            'color' => 'default',
        ],
        [
            'type' => ActionLinkEnum::Page->value,
            'pageable_type' => resolve(Page::class)->getMorphClass(),
            'pageable_id' => Page::query()->where('site_id', $page->site->id)
                ->whereHas(
                    'type',
                    /** @param Type $query */
                    fn (BuilderContract $query): BuilderContract => $query->listable()->enabled()->accessible(),
                )
                ->inRandomOrder()
                ->value('id'),
            'site_id' => $page->site->id,
        ],
        [
            'type' => ActionLinkEnum::Page->value,
            'pageable_type' => resolve(Page::class)->getMorphClass(),
            'pageable_id' => Page::query()->where('site_id', $page->site->id)
                ->whereHas(
                    'type',
                    /** @param Type $query */
                    fn (BuilderContract $query): BuilderContract => $query->listable()->enabled()->accessible(),
                )
                ->inRandomOrder()
                ->value('id'),
            'site_id' => $page->site->id,
            'color' => 'secondary',
        ],
    ];
    $widget->update(['meta' => $meta]);
    $widgetTranslation = Translation::factory()->translatable($widget)->language($site->language)->create();

    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-default',
        );
});

it('skips incomplete page actions when rendering default widget actions', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->defaultWidget();
    $meta = $widget->meta;
    $meta['actions'] = [
        [
            'type' => ActionLinkEnum::Page->value,
            'pageable_type' => resolve(Page::class)->getMorphClass(),
            'pageable_id' => null,
            'site_id' => $site->id,
        ],
        [
            'type' => ActionLinkEnum::Link->value,
            'url' => 'https://example.com',
            'label' => 'External',
        ],
    ];
    $widget->update(['meta' => $meta]);

    Translation::factory()->translatable($widget)->language($site->language)->create();

    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertSee('External');
});
