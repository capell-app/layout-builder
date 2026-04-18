<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Mosaic\Filament\Resources\Sections\SectionResource;
use Capell\Mosaic\Filament\Resources\Widgets\WidgetResource;
use Capell\Mosaic\Models\Section;
use Capell\Mosaic\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResult;

uses(CreatesAdminUser::class)
    ->group('global-search');

beforeEach(function (): void {
    test()->actingAsAdmin();

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setServingStatus();
});

it('finds content', function (string $searchTerm): void {
    $contentNameToken = 'capell-layout-content-name-token';
    $contentTitleToken = 'capell-layout-content-title-token';

    $language = Language::factory()->create();

    $content = Section::factory()->create([
        'name' => $contentNameToken,
    ]);

    $content->translations()->create([
        'language_id' => $language->id,
        'title' => $contentTitleToken,
    ]);

    $results = Filament::getGlobalSearchProvider()->getResults($searchTerm);
    $contentResult = $results?->getCategories()->get(SectionResource::getPluralModelLabel())?->first();

    expect($contentResult)
        ->toBeInstanceOf(GlobalSearchResult::class)
        ->and($contentResult->title)->toBe($content->name)
        ->and($contentResult->url)->toBe(SectionResource::getUrl('edit', ['record' => $content]));
})->with([
    'name' => ['capell-layout-content-name-token'],
    'title' => ['capell-layout-content-title-token'],
]);

it('finds a widget', function (string $searchTerm): void {
    $widgetNameToken = 'capell-layout-widget-name-token';
    $widgetKeyToken = 'capell-layout-widget-key-token';
    $widgetTitleToken = 'capell-layout-widget-title-token';
    $widgetComponentToken = 'capell-layout-widget-component-token';
    $widgetFileToken = 'capell-layout-widget-file-token';

    $language = Language::factory()->create();

    $widget = Widget::factory()->create([
        'name' => $widgetNameToken,
        'key' => $widgetKeyToken,
        'meta' => [
            'component' => $widgetComponentToken,
            'file' => $widgetFileToken,
        ],
    ]);

    $widget->translations()->create([
        'language_id' => $language->id,
        'title' => $widgetTitleToken,
    ]);

    $results = Filament::getGlobalSearchProvider()->getResults($searchTerm);
    $widgetResult = $results?->getCategories()->get(WidgetResource::getPluralModelLabel())?->first();

    expect($widgetResult)
        ->toBeInstanceOf(GlobalSearchResult::class)
        ->and($widgetResult->title)->toBe($widget->name)
        ->and($widgetResult->url)->toBe(WidgetResource::getUrl('edit', ['record' => $widget]));
})->with([
    'name' => ['capell-layout-widget-name-token'],
    'key' => ['capell-layout-widget-key-token'],
    'title' => ['capell-layout-widget-title-token'],
    'component' => ['capell-layout-widget-component-token'],
    'file' => ['capell-layout-widget-file-token'],
]);
