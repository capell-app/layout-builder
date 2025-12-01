<?php

declare(strict_types=1);

use Capell\Layout\Livewire\Layout\WidgetTableSelect;
use Capell\Layout\Models\Widget;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;

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

it('calls selectRecords and dispatches event with containerKey', function (): void {
    $widgets = Widget::factory()
        ->count(4)
        ->create();

    $widget = $widgets->random();

    $containerKey = 'container-123';

    livewire(WidgetTableSelect::class, ['containerKey' => $containerKey])
        ->assertSuccessful()
        ->assertCountTableRecords(4)
        ->assertCanSeeTableRecords($widgets)
        ->set('selectedTableRecords', [$widget->getKey()])
        ->call('selectRecords')
        ->assertHasNoErrors()
        ->assertDispatched(
            'add-widgets-to-container',
            containerKey: $containerKey,
            widgets: [$widget->getKey()],
        );
});

it('calls selectRecords and dispatches event with containers form', function (): void {
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
        ->set('selectedTableRecords', [$widget->getKey()])
        ->fillForm([
            'container' => $containerKey,
        ])
        ->assertSchemaStateSet([
            'container' => $containerKey,
        ])
        ->call('selectRecords')
        ->assertHasNoErrors()
        ->assertDispatched(
            'add-widgets-to-container',
            containerKey: $containerKey,
            widgets: [$widget->getKey()],
        );
});
