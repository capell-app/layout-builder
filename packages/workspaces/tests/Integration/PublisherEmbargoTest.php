<?php

declare(strict_types=1);

use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Exceptions\EmbargoActiveException;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Publisher;
use Capell\Workspaces\Tests\Integration\Fixtures\WorkspaceDraftableFixture;
use Capell\Workspaces\WorkspaceRegistry;
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

function makeEmbargoedDraft(Workspace $workspace): void
{
    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $workspace->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'embargoed draft',
        ]);
}

it('blocks publishing before the workspace embargo date', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 09:00:00', 'UTC'));

    $workspace = Workspace::factory()->approved()->create([
        'embargo_until' => CarbonImmutable::parse('2026-05-02 09:00:00', 'UTC'),
    ]);

    makeEmbargoedDraft($workspace);

    expect(fn (): mixed => (new Publisher)->publish($workspace))
        ->toThrow(EmbargoActiveException::class);

    expect($workspace->fresh()->status)->toBe(WorkspaceStatusEnum::Approved);
});

it('publishes after the workspace embargo date has passed', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-02 09:01:00', 'UTC'));

    $workspace = Workspace::factory()->approved()->create([
        'embargo_until' => CarbonImmutable::parse('2026-05-02 09:00:00', 'UTC'),
    ]);

    makeEmbargoedDraft($workspace);

    (new Publisher)->publish($workspace);

    expect($workspace->fresh()->status)->toBe(WorkspaceStatusEnum::Published);
});
