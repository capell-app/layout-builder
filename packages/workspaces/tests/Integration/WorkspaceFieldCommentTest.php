<?php

declare(strict_types=1);

use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Models\WorkspaceFieldComment;

it('persists a field comment and resolves/reopens it', function (): void {
    $workspace = Workspace::factory()->create();

    $comment = WorkspaceFieldComment::query()->create([
        'workspace_id' => $workspace->id,
        'entity_type' => 'page',
        'entity_uuid' => 'abc-123',
        'field_path' => 'title',
        'body' => 'This copy feels off.',
    ]);

    expect($comment->isResolved())->toBeFalse();

    $comment->resolve();
    expect($comment->fresh()->isResolved())->toBeTrue();

    $comment->reopen();
    expect($comment->fresh()->isResolved())->toBeFalse();
});

it('queries unresolved comments for a given field path', function (): void {
    $workspace = Workspace::factory()->create();

    WorkspaceFieldComment::query()->create([
        'workspace_id' => $workspace->id,
        'entity_type' => 'page',
        'entity_uuid' => 'abc-123',
        'field_path' => 'title',
        'body' => 'open',
    ]);
    WorkspaceFieldComment::query()->create([
        'workspace_id' => $workspace->id,
        'entity_type' => 'page',
        'entity_uuid' => 'abc-123',
        'field_path' => 'title',
        'body' => 'closed',
        'resolved_at' => now(),
    ]);

    $unresolved = WorkspaceFieldComment::query()
        ->where('entity_uuid', 'abc-123')
        ->where('field_path', 'title')
        ->whereNull('resolved_at')
        ->get();

    expect($unresolved)->toHaveCount(1)
        ->and($unresolved->first()->body)->toBe('open');
});
