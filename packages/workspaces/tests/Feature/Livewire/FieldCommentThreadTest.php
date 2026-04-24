<?php

declare(strict_types=1);

use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Livewire\FieldCommentThread;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Models\WorkspaceFieldComment;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('workspace');

it('posts a comment and appears in the thread', function (): void {
    $user = test()->createUser();
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
    $user = test()->createUser();
    test()->actingAs($user);

    $workspace = Workspace::factory()->create();

    $comment = WorkspaceFieldComment::create([
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
    $user = test()->createUser();
    test()->actingAs($user);

    $workspace = Workspace::factory()->create();

    $comment = WorkspaceFieldComment::create([
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
    $user = test()->createUser();
    test()->actingAs($user);

    $workspace = Workspace::factory()->create();

    $resolvedComment = WorkspaceFieldComment::create([
        'workspace_id' => $workspace->id,
        'entity_type' => 'page',
        'entity_uuid' => 'test-uuid-4',
        'field_path' => 'meta',
        'author_type' => $user->getMorphClass(),
        'author_id' => $user->id,
        'body' => 'Already resolved.',
        'resolved_at' => now(),
    ]);

    $openComment = WorkspaceFieldComment::create([
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
