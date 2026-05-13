<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\PublishingStudio\Actions\Workflow\BuildPublishingWorkflowAttentionItemsAction;
use Capell\PublishingStudio\Actions\Workflow\BuildPublishingWorkflowCommandCenterAction;
use Capell\PublishingStudio\Filament\Pages\PublishingWorkflowPage;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\WorkspaceResource;
use Capell\PublishingStudio\Models\PreviewLink;
use Capell\PublishingStudio\Models\Version;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Models\WorkspaceFieldComment;
use Capell\PublishingStudio\Models\WorkspaceReviewAssignment;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Support\Str;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class);

it('builds the publishing command center panels around the editorial lifecycle', function (): void {
    $reviewer = $this->createUserWithRole('super_admin');

    $draft = Workspace::factory()->open()->create(['created_by' => $reviewer->id]);
    $review = Workspace::factory()->inReview()->create();
    $scheduled = Workspace::factory()->scheduled(now()->addDay())->create();
    $stale = Workspace::factory()->open()->create(['updated_at' => now()->subDays(30)]);
    $published = Workspace::factory()->published()->create();
    $abandoned = Workspace::factory()->abandoned()->create();

    Page::factory()->create(['workspace_id' => $draft->id]);
    WorkspaceReviewAssignment::factory()->create([
        'workspace_id' => $review->id,
        'reviewer_type' => $reviewer->getMorphClass(),
        'reviewer_id' => $reviewer->id,
        'decision' => null,
    ]);
    WorkspaceFieldComment::query()->create([
        'workspace_id' => $draft->id,
        'entity_type' => Page::class,
        'entity_uuid' => 'page-1',
        'field_path' => 'title',
        'author_type' => $reviewer->getMorphClass(),
        'author_id' => $reviewer->id,
        'body' => 'Needs a sharper title.',
    ]);
    PreviewLink::query()->create([
        'workspace_id' => $draft->id,
        'token' => PreviewLink::generateToken(),
        'issued_at' => now(),
        'expires_at' => now()->addDay(),
        'access_count' => 0,
    ]);
    Version::query()->create([
        'uuid' => (string) Str::uuid(),
        'number' => 1,
        'name' => 'Live',
        'is_live' => true,
        'manifest' => [],
        'source_workspace_id' => $published->id,
        'published_at' => now(),
    ]);

    $panels = BuildPublishingWorkflowCommandCenterAction::run($reviewer);

    expect(collect($panels)->pluck('label')->all())->toBe([
        'Drafting',
        'Review',
        'Scheduling',
        'Publishing risks',
        'Published history',
        'Recovery',
    ]);

    $actions = collect($panels)->flatMap(fn ($panel): array => $panel->actions);

    expect($actions->firstWhere('label', 'Open drafts')?->count)->toBeGreaterThanOrEqual(2)
        ->and($actions->firstWhere('label', 'Open drafts')?->url)->toContain('activeTab=open')
        ->and($actions->firstWhere('label', 'Assigned to me')?->count)->toBe(1)
        ->and($actions->firstWhere('label', 'Assigned to me')?->url)->toContain('workflow=assigned-to-me')
        ->and($actions->firstWhere('label', 'Scheduled workspaces')?->count)->toBe(1)
        ->and($actions->firstWhere('label', 'Scheduled workspaces')?->url)->not->toBe(WorkspaceResource::getUrl())
        ->and($actions->firstWhere('label', 'Stale drafts')?->count)->toBeGreaterThanOrEqual(1)
        ->and($actions->firstWhere('label', 'Published versions')?->count)->toBeGreaterThanOrEqual(1)
        ->and($actions->firstWhere('label', 'Published versions')?->url)->toContain('workflow=published-versions')
        ->and($actions->firstWhere('label', 'Abandoned workspaces')?->count)->toBe(1);
});

it('hides zero-count and unauthorized workflow actions', function (): void {
    Permission::findOrCreate('View:PublishingWorkflowPage', 'web');
    Permission::findOrCreate('View:Workspace', 'web');

    $user = $this->createUser();
    $user->givePermissionTo('View:PublishingWorkflowPage');
    $user->givePermissionTo('View:Workspace');

    Version::query()->create([
        'uuid' => (string) Str::uuid(),
        'number' => 1,
        'name' => 'Live',
        'is_live' => true,
        'manifest' => [],
        'published_at' => now(),
    ]);

    $actions = collect(BuildPublishingWorkflowCommandCenterAction::run($user))
        ->flatMap(fn ($panel): array => $panel->actions);

    expect($actions->pluck('count')->all())->not->toContain(0)
        ->and($actions->pluck('label')->all())->not->toContain('Rollback-ready versions');
});

it('renders an empty workflow state when nothing needs attention', function (): void {
    $this->actingAs($this->createUserWithRole('super_admin'));

    livewire(PublishingWorkflowPage::class)
        ->assertOk()
        ->assertSee('No active publishing work')
        ->assertDontSee('Drafting');
});

it('publishes workflow attention items for the core dashboard entry point', function (): void {
    $user = $this->createUserWithRole('super_admin');
    Workspace::factory()->inReview()->create();

    $items = BuildPublishingWorkflowAttentionItemsAction::run($user);

    expect($items)
        ->not->toBeEmpty()
        ->and($items[0]->packageName)->toBe('capell-app/publishing-studio')
        ->and($items[0]->routeName)->toBeNull()
        ->and($items[0]->url)->toBe(PublishingWorkflowPage::getUrl())
        ->and($items[0]->count)->toBeGreaterThanOrEqual(1)
        ->and($items[0]->owner)->toBe('Publishing Studio');
});
