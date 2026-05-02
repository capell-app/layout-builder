<?php

declare(strict_types=1);

use Capell\Blog\Filament\Widgets\TrafficChartWidgetAbstract;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('widget');

it('renders for an admin user', function (): void {
    test()->actingAsAdmin();
    livewire(TrafficChartWidgetAbstract::class)->assertOk();
});

it('shows site traffic heading', function (): void {
    test()->actingAsAdmin();
    livewire(TrafficChartWidgetAbstract::class)
        ->assertOk()
        ->assertSee('Site traffic');
});
