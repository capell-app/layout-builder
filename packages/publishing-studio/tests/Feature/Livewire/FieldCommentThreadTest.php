<?php

declare(strict_types=1);

use Capell\PublishingStudio\Livewire\FieldCommentThread;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Models\WorkspaceFieldComment;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)->group('workspace');

beforeEach(function (): void {
    Permission::findOrCreate('View:Workspace');
    Permission::findOrCreate('Update:Workspace');
});

it('posts a comment and appears in the thread', function (): void {
    $user = test()->createUserWithPermission(['View:Workspace', 'Update:Workspace']);
    test()->actingAs($user);

    $workspace = Workspace::factory()->create();

    livewire(FieldCommentThread::class, [
        'workspaceId' => $workspace->id,
        'entityType' => 'page',
        'entityUuid' => 'test-uuid-1',
        'fieldPath' => 'title',
    ])
        ->set('newComment', 'This title needs updating.')
        ->call('postComment')
        ->assertDispatched('comment-posted');

    $comment = WorkspaceFieldComment::query()
        ->where('workspace_id', $workspace->id)
        ->where('field_path', 'title')
        ->first();

    expect($comment)->not->toBeNull()
        ->and($comment->body)->toBe('This title needs updating.')
        ->and($comment->author_type)->not->toBeNull();
});

it('resolves a comment and sets resolved_at', function (): void {
    $user = test()->createUserWithPermission(['View:Workspace', 'Update:Workspace']);
    test()->actingAs($user);

    $workspace = Workspace::factory()->create();

    $comment = WorkspaceFieldComment::query()->create([
        'workspace_id' => $workspace->id,
        'entity_type' => 'page',
        'entity_uuid' => 'test-uuid-2',
        'field_path' => 'body',
        'author_type' => $user->getMorphClass(),
        'author_id' => $user->id,
        'body' => 'Please clarify this section.',
    ]);

    livewire(FieldCommentThread::class, [
        'workspaceId' => $workspace->id,
        'entityType' => 'page',
        'entityUuid' => 'test-uuid-2',
        'fieldPath' => 'body',
    ])
        ->call('resolveComment', $comment->id);

    expect($comment->fresh()->isResolved())->toBeTrue();
});

it('reopens a resolved comment and clears resolved_at', function (): void {
    $user = test()->createUserWithPermission(['View:Workspace', 'Update:Workspace']);
    test()->actingAs($user);

    $workspace = Workspace::factory()->create();

    $comment = WorkspaceFieldComment::query()->create([
        'workspace_id' => $workspace->id,
        'entity_type' => 'page',
        'entity_uuid' => 'test-uuid-3',
        'field_path' => 'slug',
        'author_type' => $user->getMorphClass(),
        'author_id' => $user->id,
        'body' => 'Slug is not SEO-friendly.',
        'resolved_at' => now(),
    ]);

    livewire(FieldCommentThread::class, [
        'workspaceId' => $workspace->id,
        'entityType' => 'page',
        'entityUuid' => 'test-uuid-3',
        'fieldPath' => 'slug',
    ])
        ->call('reopenComment', $comment->id);

    expect($comment->fresh()->isResolved())->toBeFalse();
});

it('returns unresolved comments before resolved ones in getComments', function (): void {
    $user = test()->createUserWithPermission(['View:Workspace']);
    test()->actingAs($user);

    $workspace = Workspace::factory()->create();

    $resolvedComment = WorkspaceFieldComment::query()->create([
        'workspace_id' => $workspace->id,
        'entity_type' => 'page',
        'entity_uuid' => 'test-uuid-4',
        'field_path' => 'meta',
        'author_type' => $user->getMorphClass(),
        'author_id' => $user->id,
        'body' => 'Already resolved.',
        'resolved_at' => now(),
    ]);

    $openComment = WorkspaceFieldComment::query()->create([
        'workspace_id' => $workspace->id,
        'entity_type' => 'page',
        'entity_uuid' => 'test-uuid-4',
        'field_path' => 'meta',
        'author_type' => $user->getMorphClass(),
        'author_id' => $user->id,
        'body' => 'Still open.',
    ]);

    $component = livewire(FieldCommentThread::class, [
        'workspaceId' => $workspace->id,
        'entityType' => 'page',
        'entityUuid' => 'test-uuid-4',
        'fieldPath' => 'meta',
    ]);

    $comments = $component->instance()->getCommentsProperty();

    expect($comments->first()->id)->toBe($openComment->id)
        ->and($comments->last()->id)->toBe($resolvedComment->id);
});

it('requires workspace view permission when mounted', function (): void {
    $user = test()->createUser();
    test()->actingAs($user);

    $workspace = Workspace::factory()->create();

    livewire(FieldCommentThread::class, [
        'workspaceId' => $workspace->id,
        'entityType' => 'page',
        'entityUuid' => 'test-uuid-5',
        'fieldPath' => 'title',
    ])->assertForbidden();
});

it('requires workspace update permission to post comments', function (): void {
    $user = test()->createUserWithPermission(['View:Workspace']);
    test()->actingAs($user);

    $workspace = Workspace::factory()->create();

    livewire(FieldCommentThread::class, [
        'workspaceId' => $workspace->id,
        'entityType' => 'page',
        'entityUuid' => 'test-uuid-6',
        'fieldPath' => 'title',
    ])->set('newComment', 'Needs more context.')
        ->call('postComment')
        ->assertForbidden();
});

it('does not resolve comments outside the mounted thread', function (): void {
    $user = test()->createUserWithPermission(['View:Workspace', 'Update:Workspace']);
    test()->actingAs($user);

    $workspace = Workspace::factory()->create();
    $otherWorkspace = Workspace::factory()->create();

    $comment = WorkspaceFieldComment::query()->create([
        'workspace_id' => $otherWorkspace->id,
        'entity_type' => 'page',
        'entity_uuid' => 'test-uuid-7',
        'field_path' => 'title',
        'body' => 'Different workspace comment.',
    ]);

    expect(fn (): mixed => livewire(FieldCommentThread::class, [
        'workspaceId' => $workspace->id,
        'entityType' => 'page',
        'entityUuid' => 'test-uuid-7',
        'fieldPath' => 'title',
    ])->call('resolveComment', $comment->id))->toThrow(ModelNotFoundException::class);

    expect($comment->fresh()->isResolved())->toBeFalse();
});
