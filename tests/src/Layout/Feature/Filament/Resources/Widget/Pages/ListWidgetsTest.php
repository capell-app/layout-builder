<?php

declare(strict_types=1);

use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Mosaic\Filament\Resources\Widgets\Pages\ListWidgets;
use Capell\Mosaic\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Str;

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

    $name = fake()->unique()->name();

    livewire(ListWidgets::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callAction(
            TestAction::make(ReplicateAction::class)->table($widget),
            data: [
                'name' => $name,
                'key' => Str::slug($name),
            ],
        )
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(2);

    expect(Widget::query()->count())->toBe(2);

    expect(Widget::query()->firstWhere('name', $name))
        ->toBeInstanceOf(Widget::class)
        ->name->toBe($name);
});

test('can delete widget', function (): void {
    $widget = Widget::factory()->create();

    livewire(ListWidgets::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callAction(TestAction::make(DeleteAction::class)->table($widget))
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(0);

    assertSoftDeleted($widget, ['id' => $widget->id]);
});

test('can group delete widgets', function (): void {
    $widgets = Widget::factory()->count(5)->create();

    livewire(ListWidgets::class)
        ->assertSuccessful()
        ->selectTableRecords($widgets->pluck('id')->toArray())
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
        ->assertHasNoFormErrors();

    foreach ($widgets as $widget) {
        assertSoftDeleted($widget, ['id' => $widget->id]);
    }
});
