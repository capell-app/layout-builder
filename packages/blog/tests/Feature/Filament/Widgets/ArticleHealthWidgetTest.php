<?php

declare(strict_types=1);

use Capell\Blog\Filament\Widgets\ArticleHealthWidgetAbstract;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Role;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    Role::findOrCreate(config('capell.roles.developer', 'developer'));
});

it('renders for a developer user', function (): void {
    test()->actingAsRole(config('capell.roles.developer', 'developer'));

    livewire(ArticleHealthWidgetAbstract::class)->assertOk();
});
