<?php

declare(strict_types=1);

use Capell\Layout\Livewire\Filament\LayoutBuilder\WidgetTableSelect;
use Capell\Layout\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('widgets');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('renders the widgets table', function (): void {
    $widgets = Widget::factory()
        ->count(4)
        ->create();

    $widget = $widgets->random();

    livewire(WidgetTableSelect::class)
        ->assertSuccessful()
        ->assertCountTableRecords(4)
        ->assertCanSeeTableRecords($widgets)
        ->filterTable('type_id', $widget->type_id)
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$widget]);
});

it('renders empty state when no widgets exist', function (): void {
    livewire(WidgetTableSelect::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

it('searches widgets', function (): void {
    $widgets = Widget::factory()
        ->count(4)
        ->create();

    $widget = $widgets->random();

    livewire(WidgetTableSelect::class)
        ->toggleAllTableColumns()
        ->assertSuccessful()
        ->assertCountTableRecords(4)
        ->assertCanSeeTableRecords($widgets)
        ->searchTable($widget->name)
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$widget]);
});

it('dispatches add-widgets-to-container event with selected widget and containerKey', function (): void {
    $widgets = Widget::factory()
        ->count(4)
        ->create();

    $widget = $widgets->random();

    $containerKey = 'container-123';

    livewire(WidgetTableSelect::class, ['containerKey' => $containerKey])
        ->assertSuccessful()
        ->assertCountTableRecords(4)
        ->assertCanSeeTableRecords($widgets)
        ->set('selectedRecords', [$widget->getKey()])
        ->call('selectRecords')
        ->assertHasNoErrors()
        ->assertDispatched(
            'add-widgets-to-container',
            containerKey: $containerKey,
            widgets: [$widget->getKey()],
        );
});

it('dispatches event with multiple selected widgets', function (): void {
    $widgets = Widget::factory()
        ->count(3)
        ->create();

    $containerKey = 'container-123';

    livewire(WidgetTableSelect::class, ['containerKey' => $containerKey])
        ->assertSuccessful()
        ->set('selectedRecords', $widgets->pluck('id')->map(fn ($id): string => (string) $id)->all())
        ->call('selectRecords')
        ->assertHasNoErrors()
        ->assertDispatched(
            'add-widgets-to-container',
            containerKey: $containerKey,
            widgets: $widgets->pluck('id')->map(fn ($id): string => (string) $id)->all(),
        );
});

it('shows warning notification when selecting records without any selected', function (): void {
    Widget::factory()
        ->count(2)
        ->create();

    livewire(WidgetTableSelect::class, ['containerKey' => 'container-123'])
        ->assertSuccessful()
        ->set('selectedRecords', [])
        ->call('selectRecords')
        ->assertNotDispatched('add-widgets-to-container');
});

it('dispatches event with selected container from containers form', function (): void {
    $widgets = Widget::factory()
        ->count(4)
        ->create();

    $widget = $widgets->random();

    $containers = [
        'container-123' => 'Container 123',
        'container-456' => 'Container 456',
    ];

    $containerKey = array_key_first($containers);

    livewire(WidgetTableSelect::class, ['containers' => collect([$containers])])
        ->assertSuccessful()
        ->assertCountTableRecords(4)
        ->assertCanSeeTableRecords($widgets)
        ->set('selectedRecords', [$widget->getKey()])
        ->fillForm(
            [
                'container' => $containerKey,
            ],
            'form',
        )
        ->assertSchemaStateSet(
            [
                'container' => $containerKey,
            ],
            'form',
        )
        ->call('selectRecords')
        ->assertHasNoErrors()
        ->assertDispatched(
            'add-widgets-to-container',
            containerKey: $containerKey,
            widgets: [$widget->getKey()],
        );
});
