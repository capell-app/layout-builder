<?php

declare(strict_types=1);

use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Exceptions\ReleaseWindowClosedException;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Publisher;
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
});

it('throws ReleaseWindowClosedException when publishing outside the window', function (): void {
    config()->set('capell.publishing-studio.release_windows.enabled', true);
    config()->set('capell.publishing-studio.release_windows.timezone', 'UTC');
    config()->set('capell.publishing-studio.release_windows.windows', [
        ['days' => ['mon'], 'start' => '09:00', 'end' => '17:00'],
    ]);

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-18 10:00:00', 'UTC'));

    $workspace = Workspace::factory()->approved()->create();
    WorkspaceDraftableFixture::query()->withoutGlobalScopes()->create([
        'workspace_id' => $workspace->id,
        'uuid' => (string) Str::uuid(),
        'name' => 'draft',
    ]);

    (new Publisher)->publish($workspace);
})->throws(ReleaseWindowClosedException::class);

it('publishes when the release window is open', function (): void {
    config()->set('capell.publishing-studio.release_windows.enabled', true);
    config()->set('capell.publishing-studio.release_windows.timezone', 'UTC');
    config()->set('capell.publishing-studio.release_windows.windows', [
        ['days' => ['mon', 'tue', 'wed', 'thu', 'fri'], 'start' => '09:00', 'end' => '17:00'],
    ]);

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-14 10:00:00', 'UTC'));

    $workspace = Workspace::factory()->approved()->create();
    WorkspaceDraftableFixture::query()->withoutGlobalScopes()->create([
        'workspace_id' => $workspace->id,
        'uuid' => (string) Str::uuid(),
        'name' => 'draft',
    ]);

    $version = (new Publisher)->publish($workspace);

    expect($version->is_live)->toBeTrue()
        ->and($workspace->refresh()->status)->toBe(WorkspaceStatusEnum::Published);
});

it('publishes outside a window when bypassWindow is true', function (): void {
    config()->set('capell.publishing-studio.release_windows.enabled', true);
    config()->set('capell.publishing-studio.release_windows.timezone', 'UTC');
    config()->set('capell.publishing-studio.release_windows.windows', [
        ['days' => ['mon'], 'start' => '09:00', 'end' => '17:00'],
    ]);

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-18 10:00:00', 'UTC'));

    $workspace = Workspace::factory()->approved()->create();
    WorkspaceDraftableFixture::query()->withoutGlobalScopes()->create([
        'workspace_id' => $workspace->id,
        'uuid' => (string) Str::uuid(),
        'name' => 'draft',
    ]);

    $version = (new Publisher)->publish($workspace, bypassWindow: true);

    expect($version->is_live)->toBeTrue();
});
