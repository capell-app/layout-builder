<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Mosaic\Database\Factories\LayoutFactory;
use Capell\Mosaic\Enums\WidgetComponentEnum;
use Capell\Mosaic\Models\Section;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Pest\Expectation;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('creates ap feature list widget with expected meta', function (): void {
    $widget = resolve(WidgetCreator::class)->apFeatureListWidget();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('ap-feature-list')
        ->meta->scoped(
            fn (Expectation $expectation) => $expectation->component->toBe(WidgetComponentEnum::ApFeatureList->value),
        );
});

it('renders ap feature list widget title on page', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = resolve(WidgetCreator::class)->apFeatureListWidget();
    $translation = Translation::factory()->translatable($widget)->language($site->language)->create();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-ap-feature-list',
            fn (AssertElement $element): BaseAssert => $element->containsText($translation->title),
        );
});

it('renders ap feature list widget features from assets', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = resolve(WidgetCreator::class)->apFeatureListWidget();
    Translation::factory()->translatable($widget)->language($site->language)->create();

    $speed = Section::factory()->create(['name' => 'Speed', 'meta' => ['icon' => '⚡']]);
    $speed->translations()->create(['language_id' => $site->language->id, 'title' => 'Speed', 'content' => '<p>Blazing fast performance</p>']);
    $widget->assets()->create(['asset_id' => $speed->id, 'asset_type' => (new Section)->getMorphClass()]);

    $security = Section::factory()->create(['name' => 'Security', 'meta' => ['icon' => '🔒']]);
    $security->translations()->create(['language_id' => $site->language->id, 'title' => 'Security', 'content' => '<p>Enterprise-grade protection</p>']);
    $widget->assets()->create(['asset_id' => $security->id, 'asset_type' => (new Section)->getMorphClass()]);

    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-ap-feature-list',
            fn (AssertElement $element): BaseAssert => $element
                ->containsText('Speed')
                ->containsText('Security'),
        );
});
