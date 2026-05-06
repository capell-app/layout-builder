<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Core\Models\Page;
use Capell\PublishingStudio\Actions\CopyOnWriteAction;
use Capell\PublishingStudio\Enums\WorkspaceKindEnum;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
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

it('runs the full live -> draft -> publish cycle', function (): void {
    $page = Page::factory()->withTranslations()->create(['name' => 'About']);

    // Step 1: save as draft (new workspace)
    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->callAction('saveAsDraft', data: ['location' => 'new'])
        ->assertHasNoActionErrors();

    $workspace = Workspace::query()
        ->where('kind', WorkspaceKindEnum::SinglePageDraft)
        ->latest('id')
        ->first();

    expect($workspace)->not->toBeNull();

    $draft = Page::query()->withoutGlobalScopes()
        ->where('uuid', $page->uuid)
        ->where('workspace_id', $workspace->id)
        ->first();

    expect($draft)->not->toBeNull();

    // The Publisher only accepts Approved/Scheduled. A freshly saved draft
    // starts in Open — approve it here so publish can proceed. In the real UI
    // this happens via the approval flow (see Task 5).
    $workspace->update(['status' => WorkspaceStatusEnum::Approved]);

    // Step 2: publish the draft
    Livewire::test(EditPage::class, ['record' => $draft->getRouteKey()])
        ->assertActionVisible('publish')
        ->callAction('publish')
        ->assertNotified();

    // Step 3: the page should now be live, the workspace published
    expect(Page::query()->where('uuid', $page->uuid)->where('workspace_id', 0)->exists())->toBeTrue()
        ->and($workspace->fresh()->status)->toBe(WorkspaceStatusEnum::Published);
});

it('disables publish while in review and enables after approval', function (): void {
    $live = Page::factory()->withTranslations()->create();
    $workspace = Workspace::factory()->create(['status' => WorkspaceStatusEnum::InReview]);
    $draft = (new CopyOnWriteAction)->cloneForEdit(
        $live->fresh()->fill(['name' => 'updated']),
        $workspace,
    );

    Livewire::test(EditPage::class, ['record' => $draft->getRouteKey()])
        ->assertActionDisabled('publish');

    $workspace->update(['status' => WorkspaceStatusEnum::Approved]);

    Livewire::test(EditPage::class, ['record' => $draft->fresh()->getRouteKey()])
        ->assertActionVisible('publish')
        ->assertActionEnabled('publish');
});
