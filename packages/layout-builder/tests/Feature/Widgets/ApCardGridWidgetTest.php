<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Database\Factories\LayoutFactory;
use Capell\LayoutBuilder\Enums\WidgetComponentEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Pest\Expectation;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('creates ap card grid widget with expected meta', function (): void {
    $widget = resolve(WidgetCreator::class)->apCardGridWidget();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('ap-card-grid')
        ->meta->scoped(
            fn (Expectation $expectation) => $expectation->component->toBe(WidgetComponentEnum::ApCardGrid->value),
        );
});

it('renders ap card grid widget title on page', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = resolve(WidgetCreator::class)->apCardGridWidget();
    $translation = Translation::factory()->translatable($widget)->language($site->language)->create();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-ap-card-grid',
            fn (AssertElement $element): BaseAssert => $element->containsText($translation->title),
        );
});

it('renders ap card grid widget cards from meta', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = resolve(WidgetCreator::class)->apCardGridWidget();
    Translation::factory()->translatable($widget)->language($site->language)->create();

    $widget->update(['meta' => array_merge($widget->meta, [
        'cards' => [
            ['title' => 'Card Alpha', 'description' => 'First card description', 'icon' => '★'],
            ['title' => 'Card Beta', 'description' => 'Second card description', 'icon' => '●'],
        ],
    ])]);

    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-ap-card-grid',
            fn (AssertElement $element): BaseAssert => $element
                ->containsText('Card Alpha')
                ->containsText('Card Beta'),
        );
});
