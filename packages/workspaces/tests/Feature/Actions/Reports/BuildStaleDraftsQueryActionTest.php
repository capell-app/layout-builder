<?php

declare(strict_types=1);

use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Actions\Reports\BuildStaleDraftsQueryAction;
use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;

uses(CreatesAdminUser::class);

test('returns a Builder scoped to stale workspaces', function (): void {
    $freshOpen = Workspace::factory()->create([
        'status' => WorkspaceStatusEnum::Open->value,
        'updated_at' => now()->subDays(3),
    ]);

    $staleOpen = Workspace::factory()->create([
        'status' => WorkspaceStatusEnum::Open->value,
        'updated_at' => now()->subDays(30),
    ]);

    $staleInReview = Workspace::factory()->create([
        'status' => WorkspaceStatusEnum::InReview->value,
        'updated_at' => now()->subDays(20),
    ]);

    $staleButPublished = Workspace::factory()->create([
        'status' => WorkspaceStatusEnum::Published->value,
        'updated_at' => now()->subDays(30),
    ]);

    $query = BuildStaleDraftsQueryAction::run(thresholdDays: 14);

    expect($query)->toBeInstanceOf(Builder::class);

    $ids = $query->pluck('id')->all();

    expect($ids)->toContain($staleOpen->id)
        ->and($ids)->toContain($staleInReview->id)
        ->and($ids)->not->toContain($freshOpen->id)
        ->and($ids)->not->toContain($staleButPublished->id);
});

test('accepts a custom threshold', function (): void {
    $sevenDaysOld = Workspace::factory()->create([
        'status' => WorkspaceStatusEnum::Open->value,
        'updated_at' => now()->subDays(7),
    ]);

    $query = BuildStaleDraftsQueryAction::run(thresholdDays: 5);

    expect($query->pluck('id')->all())->toContain($sevenDaysOld->id);
});
