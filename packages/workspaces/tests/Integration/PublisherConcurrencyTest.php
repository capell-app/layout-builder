<?php

declare(strict_types=1);

use Capell\Workspaces\Exceptions\StaleWorkspaceException;
use Capell\Workspaces\Models\Version;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Publisher;
use Capell\Workspaces\WorkspaceRegistry;
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
});

afterEach(function (): void {
    Schema::dropIfExists('workspace_draftable_fixtures');
    WorkspaceRegistry::reset();
});

it('throws StaleWorkspaceException when live advances between pre-check and transaction', function (): void {
    $initialLive = Version::query()->create([
        'uuid' => (string) Str::uuid(),
        'number' => (Version::query()->max('number') ?? 0) + 1,
        'name' => 'Initial live',
        'is_live' => true,
        'manifest' => [],
        'published_at' => now(),
    ]);

    $workspace = Workspace::factory()->approved()->create([
        'base_version_id' => $initialLive->id,
    ]);

    $publisher = new class extends Publisher
    {
        public int $lockOverrideId = 0;

        protected function lockCurrentLiveVersionId(): int
        {
            return $this->lockOverrideId;
        }
    };

    Version::query()->where('id', $initialLive->id)->update(['is_live' => false]);
    $advancedLive = Version::query()->create([
        'uuid' => (string) Str::uuid(),
        'number' => (Version::query()->max('number') ?? 0) + 1,
        'name' => 'Advanced live',
        'is_live' => true,
        'manifest' => [],
        'published_at' => now(),
    ]);

    $publisher->lockOverrideId = $advancedLive->id;

    expect(fn (): Version => $publisher->publish($workspace))
        ->toThrow(StaleWorkspaceException::class);
});

it('treats a disappearing live version as stale', function (): void {
    $initialLive = Version::query()->create([
        'uuid' => (string) Str::uuid(),
        'number' => (Version::query()->max('number') ?? 0) + 1,
        'name' => 'Initial live',
        'is_live' => true,
        'manifest' => [],
        'published_at' => now(),
    ]);

    $workspace = Workspace::factory()->approved()->create([
        'base_version_id' => $initialLive->id,
    ]);

    $publisher = new class extends Publisher
    {
        protected function lockCurrentLiveVersionId(): ?int
        {
            return null;
        }
    };

    expect(fn (): Version => $publisher->publish($workspace))
        ->toThrow(StaleWorkspaceException::class);
});
