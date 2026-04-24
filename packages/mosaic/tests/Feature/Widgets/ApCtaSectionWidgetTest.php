<?php

declare(strict_types=1);

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

it('creates ap cta section widget with expected meta', function (): void {
    $widget = resolve(WidgetCreator::class)->apCtaSectionWidget();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('ap-cta-section')
        ->meta->scoped(
            fn (Expectation $expectation) => $expectation->component->toBe(WidgetComponentEnum::ApCTASection->value),
        );
});

it('renders ap cta section widget title on page', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = resolve(WidgetCreator::class)->apCtaSectionWidget();
    $translation = Translation::factory()->translatable($widget)->language($site->language)->create();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-ap-cta-section',
            fn (AssertElement $element): BaseAssert => $element->containsText($translation->title),
        );
});

it('renders ap cta section widget primary button', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = resolve(WidgetCreator::class)->apCtaSectionWidget();
    Translation::factory()->translatable($widget)->language($site->language)->create();

    $widget->update(['meta' => array_merge($widget->meta, [
        'primary_button_text' => 'Start Free Trial',
        'primary_button_url' => '/trial',
        'secondary_button_text' => 'Learn More',
        'secondary_button_url' => '/learn',
    ])]);

    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-ap-cta-section',
            fn (AssertElement $element): BaseAssert => $element
                ->containsText('Start Free Trial')
                ->containsText('Learn More'),
        );
});
