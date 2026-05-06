<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Database\Factories\LayoutFactory;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

uses(TestingFrontend::class);

it('creates page slot widget with expected meta', function (): void {
    $creator = resolve(WidgetCreator::class);

    $widget = $creator->pageSlotWidget();

    expect($widget)->toBeInstanceOf(Widget::class)
        ->and($widget->key)->toBe('page-slot')
        ->and($widget->meta)->toBeArray()
        ->and($widget->meta['type'] ?? null)->toBe('slot');
});

it('renders page slot widget on page', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $creator = resolve(WidgetCreator::class);

    $widget = $creator->pageSlotWidget();

    $layout = (new LayoutFactory)->widgets([$widget])->create();

    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-page-slot');
});

todo('test page slot content rendering');
