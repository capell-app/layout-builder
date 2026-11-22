<?php

declare(strict_types=1);

use Capell\Core\Contracts\Capabilities\LayoutWidgetHost;
use Capell\Core\Data\Capabilities\LayoutWidgetMatchData;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Capabilities\CapabilityRegistry;
use Capell\Frontend\Data\FrontendContext;
use Capell\Frontend\Facades\Frontend;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Livewire\OpaqueWidgetReference;

it('finds only pages in the selected site that place a matching widget component and value', function (): void {
    $site = Site::factory()->createOne();
    $otherSite = Site::factory()->createOne();
    $offer = Widget::factory()->createOne(['component' => 'example.offer', 'meta' => ['catalogue' => 'spring']]);
    $oldOffer = Widget::factory()->createOne(['component' => 'example.offer', 'meta' => ['catalogue' => 'winter']]);
    $editorial = Widget::factory()->createOne(['component' => 'example.editorial', 'meta' => ['catalogue' => 'spring']]);
    $matchingLayout = Layout::factory()->createOne(['containers' => ['main' => ['widgets' => [$offer->key]]]]);
    $unrelatedLayout = Layout::factory()->createOne(['containers' => ['main' => ['widgets' => [$oldOffer->key, $editorial->key]]]]);
    $matchingPage = Page::factory()->site($site)->layout($matchingLayout)->createOne();
    Page::factory()->site($site)->layout($unrelatedLayout)->createOne();
    Page::factory()->site($otherSite)->layout($matchingLayout)->createOne();

    $host = resolve(CapabilityRegistry::class)->get(LayoutWidgetHost::class);
    $match = new LayoutWidgetMatchData('example.offer', 'catalogue', [' spring ', 'spring']);

    expect($host->pagesPlacing($match, [$site->id, $site->id, 0]))->toBe([$matchingPage->id])
        ->and($host->pagesPlacing(new LayoutWidgetMatchData('example.offer', 'catalogue', ['summer']), [$site->id]))->toBe([])
        ->and($host->pagesPlacing($match, [0]))->toBe([]);
});

it('resolves public placement metadata from the saved widget and its blueprint', function (): void {
    $language = Language::factory()->createOne();
    $site = Site::factory()->createOne();
    $blueprint = Blueprint::factory()->createOne(['meta' => ['caption' => 'Default', 'colour' => 'amber']]);
    $widget = Widget::factory()->createOne([
        'blueprint_id' => $blueprint->id,
        'meta' => ['caption' => 'Local caption', 'colour' => ''],
    ]);
    $layout = Layout::factory()->createOne([
        'containers' => ['main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 2]]]],
    ]);
    $page = Page::factory()->site($site)->layout($layout)->createOne();
    Frontend::swap(new FrontendContext($site, $language, $page, $layout, null, [], null));

    $host = resolve(CapabilityRegistry::class)->get(LayoutWidgetHost::class);
    $placement = $host->placement(OpaqueWidgetReference::encode([
        'site_id' => $site->id,
        'container_key' => 'main',
        'widget_key' => $widget->key,
        'occurrence' => 2,
    ]));

    expect($placement?->containerKey)->toBe('main')
        ->and($placement?->widgetKey)->toBe($widget->key)
        ->and($placement?->occurrence)->toBe(2)
        ->and($placement?->meta)->toMatchArray(['caption' => 'Local caption', 'colour' => 'amber']);
});
