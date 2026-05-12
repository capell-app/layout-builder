<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Core\Models\Page;
use Capell\PublishingStudio\Actions\CopyOnWriteAction;
use Capell\PublishingStudio\Enums\WorkspaceKindEnum;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\WorkspaceContext;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    Role::findOrCreate('super_admin');
    $adminUser = $this->createUser();
    $adminUser->assignRole('super_admin');
    $this->actingAs($adminUser);

    WorkspaceContext::clear();
});

afterEach(function (): void {
    WorkspaceContext::clear();
});

it('is hidden when viewing a draft page', function (): void {
    $live = Page::factory()->withTranslations()->create();
    $workspace = Workspace::factory()->create();
    $draft = (new CopyOnWriteAction)->cloneForEdit(
        $live->fresh()->fill(['name' => 'draft name']),
        $workspace,
    );

    WorkspaceContext::set($workspace);

    Livewire::test(EditPage::class, ['record' => $draft->getRouteKey()])
        ->assertActionHidden('saveAsDraft');
});

it('creates a new SinglePageDraft workspace when selecting new', function (): void {
    $page = Page::factory()->withTranslations()->create();

    $before = Workspace::query()->where('kind', WorkspaceKindEnum::SinglePageDraft)->count();

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->callAction('saveAsDraft', data: ['location' => 'new'])
        ->assertHasNoActionErrors();

    expect(Workspace::query()->where('kind', WorkspaceKindEnum::SinglePageDraft)->count())
        ->toBe($before + 1);
});

it('resolves to the selected existing workspace when selecting other', function (): void {
    $page = Page::factory()->withTranslations()->create();
    $target = Workspace::factory()->create(['name' => 'Sprint 2']);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->callAction('saveAsDraft', data: [
            'location' => 'other',
            'workspace_id' => $target->id,
        ])
        ->assertHasNoActionErrors()
        ->assertDispatched('workspace-changed', workspaceId: $target->id);
});

it('shows the saved-as-draft notification with the workspace name', function (): void {
    $page = Page::factory()->withTranslations()->create();
    $target = Workspace::factory()->create(['name' => 'Sprint 2']);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->callAction('saveAsDraft', data: [
            'location' => 'other',
            'workspace_id' => $target->id,
        ])
        ->assertNotified('Saved as draft in Sprint 2.');
});

it('defaults location to active when a workspace context is set', function (): void {
    $active = Workspace::factory()->create();
    $page = Page::factory()->withTranslations()->create();

    $component = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()]);

    WorkspaceContext::set($active);

    $component
        ->mountAction('saveAsDraft')
        ->assertSchemaStateSet(['location' => 'active']);
});
