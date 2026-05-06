<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\LayoutBuilder\Filament\Resources\Sections\SectionResource;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\LayoutBuilder\Models\Section;
use Capell\LayoutBuilder\Models\Widget;
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
    $contentNameToken = 'capell-layout-builder-content-name-token';
    $contentTitleToken = 'capell-layout-builder-content-title-token';

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
    'name' => ['capell-layout-builder-content-name-token'],
    'title' => ['capell-layout-builder-content-title-token'],
]);

it('finds a widget', function (string $searchTerm): void {
    $widgetNameToken = 'capell-layout-builder-widget-name-token';
    $widgetKeyToken = 'capell-layout-builder-widget-key-token';
    $widgetTitleToken = 'capell-layout-builder-widget-title-token';
    $widgetComponentToken = 'capell-layout-builder-widget-component-token';
    $widgetFileToken = 'capell-layout-builder-widget-file-token';

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
    'name' => ['capell-layout-builder-widget-name-token'],
    'key' => ['capell-layout-builder-widget-key-token'],
    'title' => ['capell-layout-builder-widget-title-token'],
    'component' => ['capell-layout-builder-widget-component-token'],
    'file' => ['capell-layout-builder-widget-file-token'],
]);
