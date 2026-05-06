<?php

declare(strict_types=1);

use Capell\Diagnostics\Filament\Pages\DiagnosticsPage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Permission::findOrCreate('accessDiagnostics');
});

it('documents extension authoring on the developer tools page', function (): void {
    $user = $this->createUserWithPermission('accessDiagnostics');

    $this->actingAs($user);

    Livewire::test(DiagnosticsPage::class)
        ->assertSuccessful()
        ->assertSee(__('capell-diagnostics::package.extension_authoring_heading'))
        ->assertSee('https://docs.capell.app/packages/how-to-create-a-capell-extension');
});
