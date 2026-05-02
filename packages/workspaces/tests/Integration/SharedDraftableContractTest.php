<?php

declare(strict_types=1);

use Capell\Tests\Fixtures\Concerns\HasAssertWorkspaceDraftable;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Tests\Integration\Fixtures\WorkspaceDraftableFixture;
use Capell\Workspaces\WorkspaceRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(HasAssertWorkspaceDraftable::class);

beforeEach(function (): void {
    Schema::create('workspace_draftable_fixtures', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('workspace_id')->default(0)->index();
        $table->unsignedBigInteger('shadowed_by_workspace_id')->default(0)->index();
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

it('proves the WorkspaceDraftableFixture honours the BelongsToWorkspace contract', function (): void {
    $factory = function (?Workspace $workspace): WorkspaceDraftableFixture {
        $row = new WorkspaceDraftableFixture;
        $row->forceFill([
            'workspace_id' => $workspace?->id ?? 0,
            'uuid' => (string) Str::uuid(),
            'name' => 'contract-row',
        ])->save();

        return $row;
    };

    test()->assertWorkspaceDraftableContract(WorkspaceDraftableFixture::class, $factory);

    expect(true)->toBeTrue();
});
