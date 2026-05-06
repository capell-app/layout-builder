<?php

declare(strict_types=1);

use Capell\Diagnostics\Filament\Widgets\Health\PackagesInstalledWidgetAbstract as PackagesInstalledWidget;
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

    livewire(PackagesInstalledWidget::class)->assertOk();
});
