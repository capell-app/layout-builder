<?php

declare(strict_types=1);

use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Layout\Filament\Resources\WidgetResource\Pages\ListWidgets;
use Capell\Layout\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Factories\Sequence;

use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can list widgets', function (): void {
    $widgets = Widget::factory()->count(5)->create();

    livewire(ListWidgets::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($widgets);
});

test('can search widgets', function (): void {
    $widgets = Widget::factory()
        ->sequence(fn (Sequence $sequence): array => ['name' => sprintf('Language(%d)', $sequence->index)])
        ->count(3)
        ->create();

    $name = $widgets->random()->name;

    livewire(ListWidgets::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->searchTable($name)
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords($widgets->where('name', $name))
        ->assertCanNotSeeTableRecords($widgets->where('name', '!=', $name));
});

test('can sort widgets', function (): void {
    $widgets = Widget::factory()->count(5)->create();

    livewire(ListWidgets::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->sortTable('name')
        ->assertCanSeeTableRecords($widgets->sortBy('name'), inOrder: true);
});

test('can replicate widget', function (): void {
    $widget = Widget::factory()->create();

    $name = fake()->unique()->name;

    livewire(ListWidgets::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callTableAction(
            ReplicateAction::class,
            record: $widget,
            data: [
                'name' => $name,
            ]
        )
        ->assertHasNoActionErrors()
        ->assertCountTableRecords(2);

    expect(Widget::count())->toBe(2);

    expect(Widget::firstWhere('name', $name))
        ->toBeInstanceOf(Widget::class)
        ->name->toBe($name);
});

test('can delete widget', function (): void {
    $widget = Widget::factory()->create();

    livewire(ListWidgets::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callTableAction(DeleteAction::class, $widget)
        ->assertHasNoTableActionErrors()
        ->assertCountTableRecords(0);

    assertSoftDeleted($widget, ['id' => $widget->id]);
});

test('can group delete widgets', function (): void {
    $widgets = Widget::factory()->count(5)->create();

    livewire(ListWidgets::class)
        ->assertSuccessful()
        ->callTableBulkAction(DeleteBulkAction::class, $widgets)
        ->assertHasNoTableActionErrors();

    foreach ($widgets as $widget) {
        assertSoftDeleted($widget, ['id' => $widget->id]);
    }
});
