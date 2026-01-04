<?php

declare(strict_types=1);

use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Models\Widget;
use Capell\Layout\Services\Creator\WidgetCreator;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

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
