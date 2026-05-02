<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Core\Models\Page;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Actions\CopyOnWriteAction;
use Capell\Workspaces\Filament\Resources\Pages\Pages\PageVersionHistoryPage;
use Capell\Workspaces\Models\Workspace;

use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('page');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('renders successfully', function (): void {
    $page = Page::factory()->create();

    livewire(PageVersionHistoryPage::class, ['record' => $page->getRouteKey()])
        ->assertSuccessful();
});

test('accessible via the page resource url', function (): void {
    $page = Page::factory()->create();

    get(PageResource::getUrl('history', ['record' => $page]))
        ->assertSuccessful();
});

test('shows no-drafts prompt when page has no workspace copies', function (): void {
    $page = Page::factory()->create();

    livewire(PageVersionHistoryPage::class, ['record' => $page->getRouteKey()])
        ->assertSee(__('capell-admin::message.version_history_no_drafts'));
});

test('lists workspace copy names in the timeline', function (): void {
    $live = Page::factory()->create();
    $workspace = Workspace::factory()->create(['name' => 'Sprint 42']);

    (new CopyOnWriteAction)->cloneForEdit(
        $live->fresh()->fill(['name' => 'Sprint edit']),
        $workspace,
    );

    livewire(PageVersionHistoryPage::class, ['record' => $live->getRouteKey()])
        ->assertSee('Sprint 42')
        ->assertDontSee(__('capell-admin::message.version_history_no_drafts'));
});

test('auto-selects the workspace copy on mount when there is one draft', function (): void {
    $live = Page::factory()->create();
    $workspace = Workspace::factory()->create();

    (new CopyOnWriteAction)->cloneForEdit(
        $live->fresh()->fill(['name' => 'a draft']),
        $workspace,
    );

    livewire(PageVersionHistoryPage::class, ['record' => $live->getRouteKey()])
        ->assertSet('selectedWorkspaceId', $workspace->id);
});

test('selectVersion updates the selected workspace id', function (): void {
    $live = Page::factory()->create();
    $workspace = Workspace::factory()->create();

    (new CopyOnWriteAction)->cloneForEdit(
        $live->fresh()->fill(['name' => 'draft version']),
        $workspace,
    );

    livewire(PageVersionHistoryPage::class, ['record' => $live->getRouteKey()])
        ->call('selectVersion', $workspace->id)
        ->assertSet('selectedWorkspaceId', $workspace->id);
});

test('deleteVersion removes the draft and sends a notification', function (): void {
    $live = Page::factory()->create();
    $workspace = Workspace::factory()->create(['name' => 'Removable Draft']);

    /** @var Page $draft */
    $draft = (new CopyOnWriteAction)->cloneForEdit(
        $live->fresh()->fill(['name' => 'about to be deleted']),
        $workspace,
    );

    livewire(PageVersionHistoryPage::class, ['record' => $live->getRouteKey()])
        ->call('deleteVersion', $draft->id)
        ->assertNotified(__('capell-admin::message.draft_deleted_notification', ['workspace' => 'Removable Draft']));

    expect(Page::query()->withoutGlobalScopes()->find($draft->id))->toBeNull();
});

test('deleteVersion clears the selected workspace id when deleting the active version', function (): void {
    $live = Page::factory()->create();
    $workspace = Workspace::factory()->create();

    /** @var Page $draft */
    $draft = (new CopyOnWriteAction)->cloneForEdit(
        $live->fresh()->fill(['name' => 'selected draft']),
        $workspace,
    );

    livewire(PageVersionHistoryPage::class, ['record' => $live->getRouteKey()])
        ->call('selectVersion', $workspace->id)
        ->assertSet('selectedWorkspaceId', $workspace->id)
        ->call('deleteVersion', $draft->id)
        ->assertSet('selectedWorkspaceId', null);
});

test('version history action exists on edit page with draft count in label', function (): void {
    $live = Page::factory()->create();
    $workspace = Workspace::factory()->create();

    (new CopyOnWriteAction)->cloneForEdit(
        $live->fresh()->fill(['name' => 'a draft']),
        $workspace,
    );

    livewire(EditPage::class, ['record' => $live->getRouteKey()])
        ->assertActionExists('revisions');
});

test('version history action label includes the draft count', function (): void {
    $live = Page::factory()->create();

    $workspace = Workspace::factory()->create();
    (new CopyOnWriteAction)->cloneForEdit(
        $live->fresh()->fill(['name' => 'draft']),
        $workspace,
    );

    livewire(EditPage::class, ['record' => $live->getRouteKey()])
        ->assertActionHasLabel('revisions', __('capell-admin::button.revisions', ['count' => 1]));
});
