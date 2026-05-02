<?php

declare(strict_types=1);

use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\DeveloperTools\Filament\Widgets\Health\ConfigDriftWidgetAbstract as ConfigDriftWidget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Role;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    Role::findOrCreate(config('capell.roles.super_admin', 'super_admin'));
});

it('is registered on the system health dashboard', function (): void {
    expect(CapellAdmin::getDashboardWidgets(DashboardEnum::SystemHealth))
        ->toContain(ConfigDriftWidget::class);
});

it('renders for a super admin', function (): void {
    $user = $this->createUserWithRole(config('capell.roles.super_admin', 'super_admin'));

    $this->actingAs($user);

    livewire(ConfigDriftWidget::class)
        ->assertOk()
        ->assertSee('Config drift');
});
