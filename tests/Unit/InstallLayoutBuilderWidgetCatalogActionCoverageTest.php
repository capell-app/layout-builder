<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\LayoutBuilder\Actions\InstallLayoutBuilderWidgetCatalogAction;
use Capell\LayoutBuilder\Data\LayoutWidgetCatalogDefinitionData;
use Capell\LayoutBuilder\Enums\WidgetComponentEnum;
use Capell\LayoutBuilder\Models\Widget;

it('defines every extra widget with string labels', function (): void {
    $definitions = LayoutWidgetCatalogDefinitionData::extraCatalog();

    expect($definitions)->not->toBeEmpty();

    foreach ($definitions as $definition) {
        expect($definition->name)->toBeString();

        if ($definition->hasNavigation()) {
            expect($definition->navigationName)->toBeString();
        }
    }
});

it('installs the full widget catalog with normalized enum metadata and translations', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    Widget::query()->delete();

    InstallLayoutBuilderWidgetCatalogAction::run(collect([$language]), extraWidgets: true);

    $defaultDefinitions = LayoutWidgetCatalogDefinitionData::defaultCatalog();
    $extraDefinitions = LayoutWidgetCatalogDefinitionData::extraCatalog();

    expect($defaultDefinitions)->not->toBeEmpty()
        ->and($extraDefinitions)->not->toBeEmpty()
        ->and(collect($extraDefinitions)->firstWhere('key', 'widget-navigation')?->hasNavigation())->toBeTrue()
        ->and(Widget::query()->count())->toBe(count($defaultDefinitions) + count($extraDefinitions));

    $announcementWidget = capell_test_instance(Widget::query()->firstWhere('key', 'announcement-bar'), Widget::class);
    $testimonialWidget = capell_test_instance(Widget::query()->firstWhere('key', 'asset-testimonials'), Widget::class);
    $testimonialWidgetMeta = capell_test_array($testimonialWidget->meta);
    $navigationTabsWidget = capell_test_instance(Widget::query()->firstWhere('key', 'widget-navigation-tabs'), Widget::class);

    expect($announcementWidget->component)->toBe(WidgetComponentEnum::AnnouncementBar->value)
        ->and($announcementWidget->translations()->where('language_id', $language->getKey())->exists())->toBeTrue()
        ->and($testimonialWidgetMeta['background_color'] ?? null)->toBe('gray')
        ->and($testimonialWidget->component)->toBe(WidgetComponentEnum::AssetTestimonials->value)
        ->and($navigationTabsWidget->component)->toBe(WidgetComponentEnum::NavigationTabs->value);
});

it('preserves existing component metadata while backfilling missing safe defaults', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    Widget::query()->delete();

    $existingWidget = Widget::factory()->create([
        'key' => 'announcement-bar',
    ]);
    $existingWidget->forceFill([
        'component' => 'custom-component',
        'meta' => [
            'component' => 'custom-component',
        ],
    ])->save();

    InstallLayoutBuilderWidgetCatalogAction::run(collect([$language]), extraWidgets: false);

    $widget = capell_test_instance(Widget::query()->firstWhere('key', 'announcement-bar'), Widget::class);
    $widgetMeta = capell_test_array($widget->meta);

    expect($widget->component)->toBe('custom-component')
        ->and($widgetMeta['container'] ?? null)->toBe('full')
        ->and($widgetMeta['padding'] ?? null)->toBe(['sm']);
});
