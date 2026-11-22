<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Filament\Resources\Layouts\Pages\ListLayouts;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\ListWidgets;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

uses(CreatesAdminUser::class);

it('finds layouts containing the selected widget in modern and legacy placement data', function (): void {
    $this->actingAsAdmin();
    $widget = Widget::factory()->create(['key' => 'release-note', 'name' => 'Release note']);
    $modern = Layout::factory()->create(['containers' => ['main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]]]]);
    $legacy = Layout::factory()->create(['containers' => ['main' => ['widgets' => ['unrelated-widget', $widget->key]]]]);
    $unrelated = Layout::factory()->create(['containers' => ['main' => ['widgets' => [['widget_key' => 'release-note-extra', 'occurrence' => 1]]]]]);

    Livewire::test(ListLayouts::class)
        ->filterTable('widget_key', $widget->key)
        ->assertCanSeeTableRecords([$modern, $legacy])
        ->assertCanNotSeeTableRecords([$unrelated]);
});

it('filters widgets to actual placements and returns an empty table for a layout without placements', function (): void {
    $this->actingAsAdmin();
    $placed = Widget::factory()->create(['key' => 'placed-widget']);
    $unplaced = Widget::factory()->create(['key' => 'unplaced-widget']);
    $usedLayout = Layout::factory()->create(['containers' => ['main' => ['widgets' => [['widget_key' => $placed->key, 'occurrence' => 1]]]]]);
    $emptyLayout = Layout::factory()->create(['containers' => []]);

    Livewire::test(ListWidgets::class)
        ->filterTable('layout_id', $usedLayout->getKey())
        ->assertCanSeeTableRecords([$placed])
        ->assertCanNotSeeTableRecords([$unplaced])
        ->filterTable('layout_id', $emptyLayout->getKey())
        ->assertCanNotSeeTableRecords([$placed, $unplaced]);
});

it('lists placed widgets in layout order and keeps unplaced widgets out of layout details', function (): void {
    $this->actingAsAdmin();
    $footer = Widget::factory()->create(['key' => 'footer-signup']);
    $hero = Widget::factory()->create(['key' => 'homepage-hero']);
    Widget::factory()->create(['key' => 'inventory-banner']);
    $layout = Layout::factory()->create(['containers' => ['main' => ['widgets' => [
        ['widget_key' => $hero->key, 'occurrence' => 1],
        ['widget_key' => $footer->key, 'occurrence' => 1],
    ]]]]);

    $component = Livewire::test(ListLayouts::class)
        ->mountAction(TestAction::make('info')->table($layout));

    $modal = data_get($component->__get('effects'), 'partials.action-modals');
    assert(is_string($modal));
    $heroPosition = strpos($modal, 'homepage-hero');
    $footerPosition = strpos($modal, 'footer-signup');
    assert(is_int($heroPosition));
    assert(is_int($footerPosition));
    expect($heroPosition)->toBeLessThan($footerPosition)
        ->and($modal)->not->toContain('inventory-banner');
});
