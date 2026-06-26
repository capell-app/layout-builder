<?php

declare(strict_types=1);

use Capell\Admin\Filament\Livewire\PublishStatusPanel;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(CreatesAdminUser::class)->group('widget');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

function widgetPanel(Widget $widget): Testable
{
    return Livewire::test(PublishStatusPanel::class, [
        'recordClass' => Widget::class,
        'recordId' => $widget->getKey(),
    ]);
}

it('shows the status toggle and publish controls for a live, statusable widget', function (): void {
    $widget = Widget::factory()->create([
        'status' => true,
        'visible_from' => now()->subDay(),
        'visible_until' => null,
    ]);

    widgetPanel($widget)
        ->assertOk()
        ->assertSee(__('capell-admin::publish_panel.status_active'))
        ->assertActionVisible('toggleStatus')
        ->assertActionVisible('unpublish');
});

it('toggles a widget between active and inactive', function (): void {
    $widget = Widget::factory()->create(['status' => true]);

    widgetPanel($widget)->callAction('toggleStatus');

    expect($widget->fresh()->isEnabled())->toBeFalse();
});

it('publishes a draft widget immediately via the panel', function (): void {
    $widget = Widget::factory()->create([
        'visible_from' => now()->addYears(100),
        'visible_until' => null,
    ]);

    widgetPanel($widget)->callAction('publishNow');

    expect($widget->fresh()->isPending())->toBeFalse();
});

it('unpublishes a live widget via the panel', function (): void {
    $widget = Widget::factory()->create([
        'visible_from' => now()->subDay(),
        'visible_until' => null,
    ]);

    widgetPanel($widget)->callAction('unpublish');

    expect($widget->fresh()->isExpired())->toBeTrue();
});
