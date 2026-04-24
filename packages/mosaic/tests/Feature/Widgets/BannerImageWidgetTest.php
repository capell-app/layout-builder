<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Media\Models\Media;
use Capell\Mosaic\Database\Factories\LayoutFactory;
use Capell\Mosaic\Enums\WidgetComponentEnum;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Pest\Expectation;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('creates banner image widget with expected meta', function (): void {
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->bannerImageWidget();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('banner-image')
        ->meta->scoped(
            fn (Expectation $expectation) => $expectation->component->toBe(WidgetComponentEnum::BannerImage->value),
        );
});

it('renders banner image widget on page', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->bannerImageWidget();
    $widgetTranslation = Translation::factory()->translatable($widget)->language($site->language)->create();
    $backgroundImage = Media::factory()->model($widget)->backgroundImage()->create();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-banner-image',
            fn (AssertElement $element): BaseAssert => $element->containsText($widgetTranslation->title)
                ->find(
                    'img',
                    fn (AssertElement $imgElm): BaseAssert => $imgElm->has('alt', $backgroundImage->name)
                        ->has('src', $backgroundImage->getFullUrl()),
                ),
        );
});
