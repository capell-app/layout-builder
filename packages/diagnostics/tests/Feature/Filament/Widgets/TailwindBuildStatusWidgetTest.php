<?php

declare(strict_types=1);

use Capell\Diagnostics\Filament\Widgets\Health\TailwindBuildStatusWidgetAbstract as TailwindBuildStatusWidget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Role;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    Role::findOrCreate(config('capell.roles.developer', 'developer'));
});

it('renders for a developer user', function (): void {
    $user = $this->createUser();
    $user->assignRole(config('capell.roles.developer', 'developer'));

    $this->actingAs($user);

    livewire(TailwindBuildStatusWidget::class)->assertOk();
});

it('renders successfully on repeated calls', function (): void {
    $user = $this->createUser();
    $user->assignRole(config('capell.roles.developer', 'developer'));

    $this->actingAs($user);

    livewire(TailwindBuildStatusWidget::class)->assertOk();
    livewire(TailwindBuildStatusWidget::class)->assertOk();
});
