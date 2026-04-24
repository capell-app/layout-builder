<?php

declare(strict_types=1);

use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Services\WorkspaceDiffService;
use Capell\Workspaces\Tests\Integration\Fixtures\WorkspaceDraftableFixture;
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
    WorkspaceRegistry::register(WorkspaceDraftableFixture::class);
});

afterEach(function (): void {
    Schema::dropIfExists('workspace_draftable_fixtures');
    WorkspaceRegistry::reset();
});

it('reports modified rows with only changed attributes', function (): void {
    $workspace = Workspace::factory()->open()->create();
    $uuid = (string) Str::uuid();

    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => 0,
            'uuid' => $uuid,
            'name' => 'live-name',
        ]);

    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $workspace->id,
            'uuid' => $uuid,
            'name' => 'edited-name',
        ]);

    $entries = (new WorkspaceDiffService)->diff($workspace);

    expect($entries)->toHaveCount(1);
    $entry = $entries->first();
    expect($entry['kind'])->toBe('modified')
        ->and($entry['changes'])->toHaveKey('name')
        ->and($entry['changes']['name'])->toMatchArray([
            'before' => 'live-name',
            'after' => 'edited-name',
        ]);
});

it('reports added rows with no live counterpart', function (): void {
    $workspace = Workspace::factory()->open()->create();

    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $workspace->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'brand-new',
        ]);

    $entries = (new WorkspaceDiffService)->diff($workspace);

    expect($entries)->toHaveCount(1);
    expect($entries->first()['kind'])->toBe('added');
});

it('omits rows whose only differences are ignored columns', function (): void {
    $workspace = Workspace::factory()->open()->create();
    $uuid = (string) Str::uuid();

    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => 0,
            'uuid' => $uuid,
            'name' => 'same-name',
        ]);

    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $workspace->id,
            'uuid' => $uuid,
            'name' => 'same-name',
        ]);

    $entries = (new WorkspaceDiffService)->diff($workspace);

    expect($entries)->toHaveCount(0);
});

it('renders an HTML side-by-side diff for long text', function (): void {
    $before = "The quick brown fox\njumps over the lazy dog";
    $after = "The quick red fox\njumps over the sleepy dog";

    $html = (new WorkspaceDiffService)->renderHtmlDiff($before, $after);

    expect($html)->toContain('<table')
        ->and($html)->toContain('diff');
});

it('renderHtmlDiff coerces nulls into empty strings without throwing', function (): void {
    $html = (new WorkspaceDiffService)->renderHtmlDiff(null, 'hello world');

    expect($html)->toBeString()->not->toBeEmpty();
});
