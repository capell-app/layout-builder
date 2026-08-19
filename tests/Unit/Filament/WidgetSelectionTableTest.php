<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Filament\Resources\Widgets\Tables\WidgetSelectionTable;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\Layout\View as LayoutView;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

uses(CreatesAdminUser::class);

it('configures a card gallery without changing widget selection semantics', function (): void {
    test()->actingAsAdmin();

    $activeWidget = Widget::factory()->create([
        'key' => 'selection-active-widget',
        'status' => true,
    ]);
    $disabledWidget = Widget::factory()->create([
        'key' => 'selection-disabled-widget',
        'status' => false,
    ]);

    $table = cap0180ConfigureWidgetSelectionTable();
    $columns = array_values($table->getColumns());
    $cardColumn = collect($table->getColumnsLayout())->first(
        fn (mixed $column): bool => $column instanceof LayoutView,
    );

    if (! $cardColumn instanceof LayoutView) {
        throw new RuntimeException('Expected the widget selection table to expose a card view column.');
    }
    $columnNames = collect($columns)
        ->filter(fn (mixed $column): bool => $column instanceof Column)
        ->map(fn (Column $column): string => $column->getName())
        ->values()
        ->all();

    $query = $table->getQuery();

    if (! $query instanceof Builder) {
        throw new RuntimeException('Expected the widget selection table to expose an Eloquent query.');
    }

    $loadedWidget = $query->whereKey($activeWidget)->first();

    if (! $loadedWidget instanceof Widget) {
        throw new RuntimeException('Expected the widget selection table query to load the active widget.');
    }

    expect($table->getContentGrid())->toBe([
        'sm' => 1,
        'md' => 2,
        'xl' => 3,
    ])
        ->and($cardColumn->getView())->toBe('capell-layout-builder::filament.resources.widgets.widget-card')
        ->and($table->getRecordClasses($activeWidget))->toContain('capell-layout-builder-widget-selection-card-record')
        ->and($table->isRecordSelectable($activeWidget))->toBeTrue()
        ->and($table->isRecordSelectable($disabledWidget))->toBeFalse()
        ->and($columnNames)->toContain('name', 'key')
        ->and($columnNames)->not->toContain('component', 'component_item', 'view_file', 'layouts_count', 'widget_assets_count')
        ->and($loadedWidget->getKey())->toBe($activeWidget->getKey())
        ->and($loadedWidget->key)->toBe($activeWidget->key)
        ->and($loadedWidget->getAttribute('layouts_count'))->toBe(0)
        ->and($loadedWidget->getAttribute('widget_assets_count'))->toBe(0);
});

it('renders a preview URL, accessible image text, counts, and a no-image fallback', function (): void {
    $widget = Widget::factory()->make([
        'name' => 'Hero Widget',
        'key' => 'internal-hero-key',
        'admin' => [
            'image' => 'widget-previews/hero.png',
        ],
        'status' => true,
        'widget_assets_count' => 2,
        'layouts_count' => 3,
    ]);
    $widget->setRelation('image', null);

    $html = view(
        'capell-layout-builder::filament.resources.widgets.widget-card',
        ['getRecord' => fn (): Widget => $widget],
    )->render();

    expect($html)
        ->toContain('Hero Widget')
        ->toContain('widget-previews/hero.png')
        ->toContain('alt="Preview of Hero Widget"')
        ->toContain('3 layouts')
        ->toContain('2 assets')
        ->not->toContain('internal-hero-key')
        ->not->toContain('component_item');

    $widget->setAttribute('admin', []);
    $html = view(
        'capell-layout-builder::filament.resources.widgets.widget-card',
        ['getRecord' => fn (): Widget => $widget],
    )->render();

    expect($html)
        ->toContain(__('capell-layout-builder::table.widget_preview_fallback'))
        ->toContain('role="img"')
        ->not->toContain('<img');
});

function cap0180ConfigureWidgetSelectionTable(): Table
{
    $livewire = Mockery::mock(HasTable::class);
    $table = Table::make($livewire)->query(Widget::query());

    $livewire->shouldReceive('makeFilamentTranslatableContentDriver')->andReturn(null)->byDefault();
    $livewire->shouldReceive('getTable')->andReturn($table)->byDefault();

    return WidgetSelectionTable::configure($table);
}
