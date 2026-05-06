<?php

declare(strict_types=1);

use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Publisher;
use Capell\PublishingStudio\PublishScheduledPublishingStudioJob;
use Capell\PublishingStudio\Tests\Integration\Fixtures\WorkspaceDraftableFixture;
use Capell\PublishingStudio\WorkspaceRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Schema::create('workspace_draftable_fixtures', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('workspace_id')->default(0)->index();
        $table->uuid('uuid');
        $table->string('name');
        $table->timestamps();
    });

    WorkspaceRegistry::reset();
    WorkspaceRegistry::register(WorkspaceDraftableFixture::class);
});

afterEach(function (): void {
    Schema::dropIfExists('workspace_draftable_fixtures');
    WorkspaceRegistry::reset();
    CarbonImmutable::setTestNow();
    config()->set('capell.publishing-studio.release_windows.enabled', false);
});

function seedScheduledWorkspace(string $publishAt): Workspace
{
    $workspace = Workspace::factory()->scheduled(publishAt: $publishAt)->create();

    WorkspaceDraftableFixture::query()->withoutGlobalScopes()->create([
        'workspace_id' => $workspace->id,
        'uuid' => (string) Str::uuid(),
        'name' => 'draft',
    ]);

    return $workspace;
}

it('publishes every scheduled workspace whose publish_at has elapsed', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 18:05:00', 'UTC'));

    $due = seedScheduledWorkspace('2026-05-01 18:00:00');
    $notDue = seedScheduledWorkspace('2026-05-02 09:00:00');

    (new PublishScheduledPublishingStudioJob)->handle(new Publisher);

    expect($due->fresh()->status)->toBe(WorkspaceStatusEnum::Published)
        ->and($notDue->fresh()->status)->toBe(WorkspaceStatusEnum::Scheduled);
});

it('leaves a scheduled workspace in place when the release window is closed', function (): void {
    config()->set('capell.publishing-studio.release_windows.enabled', true);
    config()->set('capell.publishing-studio.release_windows.timezone', 'UTC');
    config()->set('capell.publishing-studio.release_windows.windows', [
        ['days' => ['mon'], 'start' => '09:00', 'end' => '17:00'],
    ]);

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-18 10:00:00', 'UTC'));

    $workspace = seedScheduledWorkspace('2026-04-18 09:00:00');

    (new PublishScheduledPublishingStudioJob)->handle(new Publisher);

    expect($workspace->fresh()->status)->toBe(WorkspaceStatusEnum::Scheduled);
});

it('ignores publishing-studio whose publish_at is still in the future', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 09:00:00', 'UTC'));

    $workspace = seedScheduledWorkspace('2026-05-01 18:00:00');

    (new PublishScheduledPublishingStudioJob)->handle(new Publisher);

    expect($workspace->fresh()->status)->toBe(WorkspaceStatusEnum::Scheduled);
});
