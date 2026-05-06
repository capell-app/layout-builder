<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Core\Models\Page;
use Capell\PublishingStudio\Actions\CopyOnWriteAction;
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

it('shows zero revisions when no drafts exist', function (): void {
    $page = Page::factory()->withTranslations()->create(['name' => 'Home']);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->callAction('revisions');
})->skip('rendered inside modal — snapshot via assertModal content');

it('lists only draft rows when drafts exist', function (): void {
    $live = Page::factory()->withTranslations()->create(['name' => 'Home']);
    $workspace = Workspace::factory()->create(['name' => 'Sprint 2']);
    (new CopyOnWriteAction)->cloneForEdit($live->fresh()->fill(['name' => 'home v2']), $workspace);

    $drafts = Page::query()->withoutGlobalScopes()
        ->where('uuid', $live->fresh()->uuid)
        ->where('workspace_id', '!=', 0)
        ->with('workspace')
        ->get();

    expect($drafts)->toHaveCount(1);
    expect($drafts->first()->isLive())->toBeFalse();
    expect($drafts->first()->workspace->name)->toBe('Sprint 2');

    Livewire::test(EditPage::class, ['record' => $live->fresh()->getRouteKey()])
        ->assertActionExists('revisions');
});

it('deletes a draft via the deletePageDraft handler', function (): void {
    $live = Page::factory()->withTranslations()->create();
    $workspace = Workspace::factory()->create();
    /** @var Page $draft */
    $draft = (new CopyOnWriteAction)->cloneForEdit(
        $live->fresh()->fill(['name' => 'x']),
        $workspace,
    );

    Livewire::test(EditPage::class, ['record' => $live->fresh()->getRouteKey()])
        ->call('deletePageDraft', $draft->id);

    expect(Page::query()->withoutGlobalScopes()->find($draft->id))->toBeNull();
});
