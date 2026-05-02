<?php

declare(strict_types=1);

use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Filament\Widgets\WorkspaceActivityWidgetAbstract as WorkspaceActivityWidget;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Role;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    Role::findOrCreate('super_admin');
});

it('renders for an admin', function (): void {
    $user = $this->createUser();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    livewire(WorkspaceActivityWidget::class)->assertOk();
});
