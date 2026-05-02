<?php

declare(strict_types=1);

use Capell\Blog\Filament\Widgets\TopPagesWidgetAbstract;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('widget');

it('renders for an admin user', function (): void {
    test()->actingAsAdmin();
    livewire(TopPagesWidgetAbstract::class)->assertOk();
});

it('shows top pages heading', function (): void {
    test()->actingAsAdmin();
    livewire(TopPagesWidgetAbstract::class)
        ->assertOk()
        ->assertSee('Top pages');
});
