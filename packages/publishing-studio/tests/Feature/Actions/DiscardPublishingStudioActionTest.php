<?php

declare(strict_types=1);

use Capell\PublishingStudio\Actions\DiscardPublishingStudioAction;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Workspace;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class);

test('soft-deletes Open and InReview publishing-studio, skips others', function (): void {
    Permission::query()->firstOrCreate(['name' => 'Update:Workspace', 'guard_name' => 'web']);
    Permission::query()->firstOrCreate(['name' => 'Delete:Workspace', 'guard_name' => 'web']);
    $openWorkspace = Workspace::factory()->create([
        'status' => WorkspaceStatusEnum::Open->value,
    ]);
    $inReviewWorkspace = Workspace::factory()->create([
        'status' => WorkspaceStatusEnum::InReview->value,
    ]);
    $publishedWorkspace = Workspace::factory()->create([
        'status' => WorkspaceStatusEnum::Published->value,
    ]);

    $actor = test()->createUserWithPermission(['Update:Workspace', 'Delete:Workspace']);

    $result = DiscardPublishingStudioAction::run(
        Collection::make([$openWorkspace, $inReviewWorkspace, $publishedWorkspace]),
        $actor,
    );

    expect($result['discarded'])->toBe(2)
        ->and($result['skipped'])->toBe(1);

    expect($openWorkspace->fresh()->trashed())->toBeTrue();
    expect($inReviewWorkspace->fresh()->trashed())->toBeTrue();
    expect($publishedWorkspace->fresh()->trashed())->toBeFalse();
});
