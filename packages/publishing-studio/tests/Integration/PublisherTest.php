<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Enums\WorkspaceTransitionEnum;
use Capell\PublishingStudio\Events\WorkspaceStateChanged;
use Capell\PublishingStudio\Exceptions\StaleWorkspaceException;
use Capell\PublishingStudio\Exceptions\UrlCollisionException;
use Capell\PublishingStudio\Models\Version;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Publisher;
use Capell\PublishingStudio\Tests\Integration\Fixtures\WorkspaceDraftableFixture;
use Capell\PublishingStudio\WorkspaceRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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
    WorkspaceRegistry::register(WorkspaceDraftableFixture::class);
});

afterEach(function (): void {
    Schema::dropIfExists('workspace_draftable_fixtures');
    WorkspaceRegistry::reset();
});

function makeLiveFixture(string $uuid, string $name): WorkspaceDraftableFixture
{
    return WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => 0,
            'uuid' => $uuid,
            'name' => $name,
        ]);
}

function makeDraftFixture(Workspace $workspace, string $uuid, string $name): WorkspaceDraftableFixture
{
    return WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $workspace->id,
            'uuid' => $uuid,
            'name' => $name,
        ]);
}

it('rejects publishing a workspace that has not been approved', function (): void {
    $workspace = Workspace::factory()->open()->create();
    $publisher = new Publisher;

    $publisher->publish($workspace);
})->throws(LogicException::class);

it('publishes an approved workspace, flipping draft rows into live', function (): void {
    $workspace = Workspace::factory()->approved()->create();
    $matchingUuid = (string) Str::uuid();

    makeLiveFixture($matchingUuid, 'live-original');
    makeDraftFixture($workspace, $matchingUuid, 'workspace-edited');
    makeDraftFixture($workspace, (string) Str::uuid(), 'workspace-new');

    $publisher = new Publisher;
    $newVersion = $publisher->publish($workspace, versionName: 'Release One');

    $workspace->refresh();
    $liveRows = WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->where('workspace_id', 0)
        ->pluck('name')
        ->all();

    expect($newVersion)->toBeInstanceOf(Version::class)
        ->and($newVersion->is_live)->toBeTrue()
        ->and($newVersion->name)->toBe('Release One')
        ->and($newVersion->source_workspace_id)->toBe($workspace->id)
        ->and($workspace->status)->toBe(WorkspaceStatusEnum::Published)
        ->and($workspace->published_at)->not->toBeNull()
        ->and($liveRows)->toEqualCanonicalizing(['workspace-edited', 'workspace-new'])
        ->and(WorkspaceDraftableFixture::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->count())->toBe(0);
});

it('dispatches WorkspaceStateChanged on publish with the Published transition', function (): void {
    Event::fake([WorkspaceStateChanged::class]);

    $workspace = Workspace::factory()->approved()->create();
    makeDraftFixture($workspace, (string) Str::uuid(), 'workspace-new');

    (new Publisher)->publish($workspace, notes: 'Shipping it');

    Event::assertDispatched(
        WorkspaceStateChanged::class,
        fn (WorkspaceStateChanged $event): bool => $event->workspace->is($workspace)
            && $event->previousStatus === WorkspaceStatusEnum::Approved
            && $event->newStatus === WorkspaceStatusEnum::Published
            && $event->transition === WorkspaceTransitionEnum::Published->value
            && $event->notes === 'Shipping it',
    );
});

it('demotes the previous live version when makeLive is true', function (): void {
    $originalLiveId = Version::liveId();
    $workspace = Workspace::factory()->approved()->create();
    makeDraftFixture($workspace, (string) Str::uuid(), 'new-content');

    $publisher = new Publisher;
    $newVersion = $publisher->publish($workspace);

    $originalLive = Version::query()->find($originalLiveId);

    expect($originalLive->is_live)->toBeFalse()
        ->and($newVersion->is_live)->toBeTrue()
        ->and(Version::liveId())->toBe($newVersion->id);
});

it('saves without demoting live when makeLive is false', function (): void {
    $originalLiveId = Version::liveId();
    $workspace = Workspace::factory()->approved()->create();
    makeDraftFixture($workspace, (string) Str::uuid(), 'snapshot-content');

    $publisher = new Publisher;
    $version = $publisher->publish($workspace, makeLive: false);

    expect($version->is_live)->toBeFalse()
        ->and(Version::liveId())->toBe($originalLiveId);
});

it('refuses to publish a workspace that is behind the live version', function (): void {
    $workspace = Workspace::factory()->approved()->create([
        'base_version_id' => Version::liveId(),
    ]);

    $newerLive = Version::query()->create([
        'uuid' => (string) Str::uuid(),
        'number' => (Version::query()->max('number') ?? 0) + 1,
        'name' => 'Newer live',
        'is_live' => false,
        'manifest' => [],
        'published_at' => now(),
    ]);

    Version::query()->where('id', '!=', $newerLive->id)->update(['is_live' => false]);
    $newerLive->is_live = true;
    $newerLive->save();

    $publisher = new Publisher;
    $publisher->publish($workspace);
})->throws(StaleWorkspaceException::class);

it('detects URL collisions before flipping draft rows to live', function (): void {
    $workspace = Workspace::factory()->approved()->create();
    $site = Site::factory()->create();
    $language = Language::factory()->create();

    DB::table('page_urls')->insert([
        'workspace_id' => 0,
        'site_id' => $site->id,
        'language_id' => $language->id,
        'url' => '/about',
        'pageable_type' => 'page',
        'pageable_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('page_urls')->insert([
        'workspace_id' => $workspace->id,
        'site_id' => $site->id,
        'language_id' => $language->id,
        'url' => '/about',
        'pageable_type' => 'page',
        'pageable_id' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $publisher = new Publisher;

    $collisions = $publisher->detectUrlCollisions($workspace);
    expect($collisions)->not->toBeEmpty()
        ->and($collisions[0]['url'])->toBe('/about');

    expect(fn (): Version => $publisher->publish($workspace))->toThrow(UrlCollisionException::class);
});

it('records a manifest of published ids per model class', function (): void {
    $workspace = Workspace::factory()->approved()->create();
    makeLiveFixture((string) Str::uuid(), 'keeper');
    $draftRow = makeDraftFixture($workspace, (string) Str::uuid(), 'fresh');

    $publisher = new Publisher;
    $version = $publisher->publish($workspace);

    $manifestIds = $version->manifestIdsFor(WorkspaceDraftableFixture::class);

    expect($manifestIds)->not->toBeEmpty()
        ->and(in_array($draftRow->id, $manifestIds, true))->toBeTrue();
});
