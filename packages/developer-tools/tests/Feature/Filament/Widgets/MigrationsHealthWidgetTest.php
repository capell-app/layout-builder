<?php

declare(strict_types=1);

use Capell\DeveloperTools\Filament\Widgets\Health\MigrationsHealthWidgetAbstract as MigrationsHealthWidget;
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

    livewire(MigrationsHealthWidget::class)->assertOk();
});
