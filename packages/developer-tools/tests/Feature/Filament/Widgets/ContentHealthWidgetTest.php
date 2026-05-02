<?php

declare(strict_types=1);

use Capell\DeveloperTools\Filament\Widgets\Health\ContentHealthWidgetAbstract as ContentHealthWidget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Role;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    Role::findOrCreate(config('capell.roles.editor', 'editor'));
});

it('renders for an authenticated editor', function (): void {
    $user = $this->createUser();
    $user->assignRole(config('capell.roles.editor', 'editor'));

    $this->actingAs($user);

    livewire(ContentHealthWidget::class)->assertOk();
});
