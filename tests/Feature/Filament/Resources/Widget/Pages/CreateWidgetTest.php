<?php

declare(strict_types=1);

use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\CreateWidget;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\ListWidgets;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Support\Str;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can delete and recreate a widget with the same key and translation data', function (): void {
    $language = Language::factory()->english()->create();
    $type = Blueprint::factory()->create([
        'name' => 'Related Pages',
        'key' => 'related-pages-widget-type',
        'type' => 'widget',
        'group' => 'asset',
        'meta' => [
            'component' => 'capell-blog::widget.page.related',
        ],
        'admin' => [
            'type_configurator' => 'Widget',
            'configurator' => 'Results',
            'layout_widget_configurator' => 'Results',
            'icon' => 'heroicon-o-list-bullet',
        ],
        'status' => true,
        'default' => true,
    ]);

    $widget = Widget::factory()->create([
        'name' => 'Related Pages',
        'key' => 'related-pages',
        'blueprint_id' => $type->id,
        'status' => true,
    ]);

    $originalTranslation = Translation::factory()
        ->translatable($widget)
        ->language($language)
        ->create([
            'title' => 'Related Pages',
            'content' => '<p>Original related page widget.</p>',
        ]);

    Livewire::test(ListWidgets::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callTableAction('delete', $widget)
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(0);

    assertSoftDeleted($widget, ['id' => $widget->id]);

    Livewire::test(CreateWidget::class)
        ->assertSuccessful()
        ->set('data.translations', [])
        ->fillForm([
            'name' => 'Related Pages',
            'key' => 'related-pages',
            'blueprint_id' => $type->id,
            'status' => true,
            'translations' => [
                (string) Str::uuid() => [
                    'language_id' => $language->id,
                    'title' => 'Related Pages',
                    'content' => '<p>Recreated related page widget.</p>',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $recreatedWidget = Widget::query()
        ->where('key', 'related-pages')
        ->whereNull('deleted_at')
        ->first();
    $recreatedWidget = capell_test_instance($recreatedWidget, Widget::class);

    $recreatedWidgetType = capell_test_instance($recreatedWidget->type, Blueprint::class);

    expect($recreatedWidget->id)->not->toBe($widget->id)
        ->and($recreatedWidget->blueprint_id)->toBe($type->id)
        ->and($recreatedWidgetType->admin)->toMatchArray([
            'type_configurator' => 'Widget',
            'configurator' => 'Results',
            'layout_widget_configurator' => 'Results',
        ]);

    $recreatedTranslations = Translation::query()
        ->where('translatable_type', $recreatedWidget->getMorphClass())
        ->where('translatable_id', $recreatedWidget->id)
        ->get();
    $recreatedTranslation = capell_test_instance($recreatedTranslations->first(), Translation::class);

    expect($recreatedTranslations)->toHaveCount(1)
        ->and($recreatedTranslation->id)->not->toBe($originalTranslation->id);

    assertDatabaseHas(Translation::class, [
        'translatable_type' => $recreatedWidget->getMorphClass(),
        'translatable_id' => $recreatedWidget->id,
        'language_id' => $language->id,
        'title' => 'Related Pages',
        'content' => '<p>Recreated related page widget.</p>',
    ]);
});
