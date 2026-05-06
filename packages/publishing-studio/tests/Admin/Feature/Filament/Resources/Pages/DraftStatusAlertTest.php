<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\PublishingStudio\Actions\CopyOnWriteAction;
use Capell\PublishingStudio\Filament\Widgets\PageAlertsWidget;
use Capell\PublishingStudio\Models\Workspace;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    Role::findOrCreate('super_admin');
    $adminUser = $this->createUser();
    $adminUser->assignRole('super_admin');
    $this->actingAs($adminUser);
});

it('includes a draft status alert with workspace name when in a workspace', function (): void {
    $live = Page::factory()->create();
    $workspace = Workspace::factory()->create(['name' => 'Sprint 2']);
    $draft = (new CopyOnWriteAction)->cloneForEdit(
        $live->fresh()->fill(['name' => 'updated']),
        $workspace,
    );

    Livewire::test(PageAlertsWidget::class, ['record' => $draft->fresh()])
        ->assertSee('Draft in')
        ->assertSee('Sprint 2');
});

it('renders an Open workspace action on the draft alert', function (): void {
    $live = Page::factory()->create();
    $workspace = Workspace::factory()->create(['name' => 'Sprint 2']);
    $draft = (new CopyOnWriteAction)->cloneForEdit(
        $live->fresh()->fill(['name' => 'updated']),
        $workspace,
    );

    Livewire::test(PageAlertsWidget::class, ['record' => $draft->fresh()])
        ->assertSee('Open workspace');
});
