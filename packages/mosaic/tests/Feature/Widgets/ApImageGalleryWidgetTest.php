<?php

declare(strict_types=1);

use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
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

it('creates ap image gallery widget with expected meta', function (): void {
    $widget = resolve(WidgetCreator::class)->apImageGalleryWidget();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('ap-image-gallery')
        ->meta->scoped(
            fn (Expectation $expectation) => $expectation->component->toBe(WidgetComponentEnum::ApImageGallery->value),
        );
});

it('renders ap image gallery widget title on page', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = resolve(WidgetCreator::class)->apImageGalleryWidget();
    $translation = Translation::factory()->translatable($widget)->language($site->language)->create();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-ap-image-gallery',
            fn (AssertElement $element): BaseAssert => $element->containsText($translation->title),
        );
});

it('renders ap image gallery widget with image', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = resolve(WidgetCreator::class)->apImageGalleryWidget();
    Translation::factory()->translatable($widget)->language($site->language)->create();
    $image = Media::factory()->model($widget)->image()->create();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-ap-image-gallery',
            fn (AssertElement $element): BaseAssert => $element->find(
                'img',
                fn (AssertElement $imgElm): BaseAssert => $imgElm
                    ->has('alt', $image->name)
                    ->has('src', $image->getFullUrl()),
            ),
        );
});
