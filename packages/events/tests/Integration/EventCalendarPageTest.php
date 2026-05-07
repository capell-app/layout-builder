<?php

declare(strict_types=1);

use Capell\Events\Filament\Pages\EventCalendarPage;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    Permission::create(['name' => 'View:EventCalendarPage', 'guard_name' => 'web']);

    test()->actingAsAdmin();
    auth()->user()->givePermissionTo('View:EventCalendarPage');
});

it('renders the admin event calendar page as a livewire component', function (): void {
    Livewire::test(EventCalendarPage::class)
        ->assertSuccessful()
        ->assertSee(__('capell-events::generic.admin_calendar'))
        ->assertSee(__('capell-events::generic.admin_calendar_subheading'));
});
