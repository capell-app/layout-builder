<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Layouts\Pages\ListLayouts;
use Capell\Core\Models\Layout;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('layout');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can list layouts', function (): void {
    $layouts = Layout::factory()->count(5)->create();

    livewire(ListLayouts::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($layouts);
});
