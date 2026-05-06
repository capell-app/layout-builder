<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Actions\InstallPackageAction;
use Capell\LayoutBuilder\Models\Section;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;

beforeEach(function (): void {
    InstallPackageAction::run();
});

it('belongs to a widget', function (): void {
    $widget = Widget::factory()->create();
    $widgetAsset = WidgetAsset::factory()->widget($widget)->create();

    expect($widgetAsset->widget)->not()->toBeNull()
        ->and($widgetAsset->widget->id)->toBe($widget->id);
});

it('computes the asset_key as asset_type dot asset_id', function (): void {
    $section = Section::factory()->create();
    $widgetAsset = WidgetAsset::factory()->asset($section)->create();

    expect($widgetAsset->asset_key)->toBe($section->getMorphClass() . '.' . $section->id);
});

it('scopes ordered by the order column ascending by default', function (): void {
    $widget = Widget::factory()->create();
    WidgetAsset::factory()->widget($widget)->create(['order' => 3]);
    WidgetAsset::factory()->widget($widget)->create(['order' => 1]);
    WidgetAsset::factory()->widget($widget)->create(['order' => 2]);

    $orderedIds = WidgetAsset::query()
        ->where('widget_id', $widget->id)
        ->ordered()
        ->pluck('order')
        ->all();

    expect($orderedIds)->toBe([1, 2, 3]);
});

it('scopes ordered descending when direction is desc', function (): void {
    $widget = Widget::factory()->create();
    WidgetAsset::factory()->widget($widget)->create(['order' => 1]);
    WidgetAsset::factory()->widget($widget)->create(['order' => 2]);

    $orderedIds = WidgetAsset::query()
        ->where('widget_id', $widget->id)
        ->ordered('desc')
        ->pluck('order')
        ->all();

    expect($orderedIds)->toBe([2, 1]);
});

it('scopes alphabetical by asset translation title for a given language', function (): void {
    $language = Language::factory()->create();
    $widget = Widget::factory()->create();

    $sectionA = Section::factory()->create();
    $sectionB = Section::factory()->create();
    $sectionC = Section::factory()->create();

    foreach ([[$sectionA, 'Zebra'], [$sectionB, 'Apple'], [$sectionC, 'Mango']] as [$section, $title]) {
        Translation::factory()->create([
            'translatable_type' => $section->getMorphClass(),
            'translatable_id' => $section->id,
            'language_id' => $language->id,
            'title' => $title,
        ]);

        WidgetAsset::factory()->widget($widget)->asset($section)->create();
    }

    $titles = WidgetAsset::query()
        ->where('widget_id', $widget->id)
        ->with(['asset.translations'])
        ->alphabetical($language)
        ->get()
        ->map(fn (WidgetAsset $widgetAsset): ?string => $widgetAsset->asset->translations->where('language_id', $language->id)->first()?->title)
        ->all();

    expect($titles)->toBe(['Apple', 'Mango', 'Zebra']);
});
