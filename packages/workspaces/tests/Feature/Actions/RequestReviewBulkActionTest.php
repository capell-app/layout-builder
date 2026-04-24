<?php

declare(strict_types=1);

use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Actions\RequestReviewBulkAction;
use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class);

test('flips Open workspaces to InReview and skips others', function (): void {
    Permission::query()->firstOrCreate(['name' => 'Update:Workspace', 'guard_name' => 'web']);
    $openWorkspace = Workspace::factory()->create([
        'status' => WorkspaceStatusEnum::Open->value,
    ]);
    $inReviewWorkspace = Workspace::factory()->create([
        'status' => WorkspaceStatusEnum::InReview->value,
    ]);
    $publishedWorkspace = Workspace::factory()->create([
        'status' => WorkspaceStatusEnum::Published->value,
    ]);

    $actor = test()->createUserWithPermission('Update:Workspace');

    $result = RequestReviewBulkAction::run(
        Collection::make([$openWorkspace, $inReviewWorkspace, $publishedWorkspace]),
        $actor,
    );

    expect($result['requested'])->toBe(1)
        ->and($result['skipped'])->toBe(2);

    $openWorkspace->refresh();
    expect($openWorkspace->status)->toBe(WorkspaceStatusEnum::InReview);
});
