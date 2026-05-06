<?php

declare(strict_types=1);

use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Tests\Integration\Fixtures\WorkspaceDraftableFixture;
use Capell\PublishingStudio\WorkspaceRegistry;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

afterEach(function (): void {
    Schema::dropIfExists('workspace_draftable_fixtures');
    WorkspaceRegistry::reset();
});

it('creates publishing-studio, seeds fixture rows, and dashboard-dashboard_reports timings', function (): void {
    $exitCode = Artisan::call('capell:publishing-studio:load-test', [
        '--publishing-studio' => 3,
        '--rows-per-workspace' => 4,
        '--fresh' => true,
    ]);

    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and(Workspace::query()->where('name', 'like', 'Load-test workspace%')->count())->toBe(3)
        ->and(WorkspaceDraftableFixture::query()->withoutGlobalScopes()->count())->toBe(12)
        ->and($output)->toContain('Created 3 publishing-studio')
        ->and($output)->toContain('Seeded 12 rows');
});

it('publishes a subset when --publish is passed', function (): void {
    $exitCode = Artisan::call('capell:publishing-studio:load-test', [
        '--publishing-studio' => 2,
        '--rows-per-workspace' => 2,
        '--publish' => 1,
        '--fresh' => true,
    ]);

    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('Published 1 publishing-studio');
});
