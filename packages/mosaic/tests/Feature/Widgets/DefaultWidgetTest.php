<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Capell\Media\Models\Media;
use Capell\Mosaic\Database\Factories\LayoutFactory;
use Capell\Mosaic\Enums\ActionLinkEnum;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Support\Creator\WidgetCreator;
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
