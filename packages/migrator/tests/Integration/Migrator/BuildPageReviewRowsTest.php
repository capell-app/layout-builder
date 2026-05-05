<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Migrator\Actions\BuildPageReviewRows;
use Capell\Migrator\Data\PageReviewRow;
use Capell\Migrator\Services\Import\PackageReadResult;
use Capell\Migrator\Services\Import\ResolutionMap;
use Capell\Migrator\Services\Import\Resolvers\MatchResolution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * @param  array<string, mixed>  $overrides
 */
function pageReviewEnvelope(string $uuid, int $siteId, string $url, array $overrides = []): string
{
    $envelope = array_merge([
        'type' => 'page',
        'uuid' => $uuid,
        'id' => 101,
        'attributes' => ['title' => 'Imported Page'],
        'owned_relations' => [
            'page_urls' => [
                ['site_id' => $siteId, 'language_id' => 1, 'url' => $url],
            ],
        ],
        'shared_relations' => [
            'site' => ['ref' => 'site:' . $siteId],
        ],
        'media_bindings' => [],
    ], $overrides);

    return (string) json_encode($envelope);
}

function pageReviewPackage(string $path, string $contents): PackageReadResult
{
    return new PackageReadResult(
        archivePath: '',
        manifest: [],
        integrity: [],
        payload: [$path => $contents],
    );
}

function resolvedSiteMap(Site $site): ResolutionMap
{
    return new ResolutionMap(
        resolved: [
            'site:' . $site->getKey() => new MatchResolution(
                localId: (int) $site->getKey(),
                strategy: 'slug',
            ),
        ],
        unresolved: [],
    );
}

it('marks row as create when no URL collision exists', function (): void {
    $site = Site::factory()->create();
    $uuid = (string) Str::uuid();

    $package = pageReviewPackage(
        sprintf('pages/%s.json', $uuid),
        pageReviewEnvelope($uuid, (int) $site->getKey(), '/fresh-page'),
    );

    $rows = (new BuildPageReviewRows)->run($package, resolvedSiteMap($site));

    expect($rows)->toHaveCount(1)
        ->and($rows[0]->uuid)->toBe($uuid)
        ->and($rows[0]->primaryUrl)->toBe('/fresh-page')
        ->and($rows[0]->resolvedSiteId)->toBe((int) $site->getKey())
        ->and($rows[0]->collisionState)->toBe(PageReviewRow::COLLISION_NONE)
        ->and($rows[0]->suggestedAction)->toBe(PageReviewRow::ACTION_CREATE)
        ->and($rows[0]->conflictMessages)->toBe([]);
});

it('flags URL collisions against live pages and suggests update', function (): void {
    $site = Site::factory()->create();
    $uuid = (string) Str::uuid();

    $row = [
        'site_id' => $site->getKey(),
        'language_id' => 1,
        'url' => '/already-live',
        'status' => 'published',
        'pageable_type' => 'page',
        'pageable_id' => 9999,
        'type' => 'alias',
        'is_manual' => 0,
        'status_code' => 200,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('page_urls', 'workspace_id')) {
        $row['workspace_id'] = 0;
    }

    DB::table('page_urls')->insert($row);

    $package = pageReviewPackage(
        sprintf('pages/%s.json', $uuid),
        pageReviewEnvelope($uuid, (int) $site->getKey(), '/already-live'),
    );

    $rows = (new BuildPageReviewRows)->run($package, resolvedSiteMap($site));

    expect($rows[0]->collisionState)->toBe(PageReviewRow::COLLISION_URL_LIVE)
        ->and($rows[0]->suggestedAction)->toBe(PageReviewRow::ACTION_UPDATE)
        ->and($rows[0]->conflictMessages)->not->toBeEmpty();
});

it('flags URL collisions against other open workspaces and suggests skip', function (): void {
    if (! Schema::hasColumn('page_urls', 'workspace_id')) {
        $this->markTestSkipped('Requires workspaces package (page_urls.workspace_id column)');
    }

    $site = Site::factory()->create();
    $uuid = (string) Str::uuid();

    DB::table('page_urls')->insert([
        'workspace_id' => 77,
        'site_id' => $site->getKey(),
        'language_id' => 1,
        'url' => '/claimed-by-other',
        'status' => 'draft',
        'pageable_type' => 'page',
        'pageable_id' => 8888,
        'type' => 'alias',
        'is_manual' => 0,
        'status_code' => 200,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $package = pageReviewPackage(
        sprintf('pages/%s.json', $uuid),
        pageReviewEnvelope($uuid, (int) $site->getKey(), '/claimed-by-other'),
    );

    $rows = (new BuildPageReviewRows)->run($package, resolvedSiteMap($site));

    expect($rows[0]->collisionState)->toBe(PageReviewRow::COLLISION_URL_WORKSPACE)
        ->and($rows[0]->suggestedAction)->toBe(PageReviewRow::ACTION_SKIP);
});

it('reports workspace collision when a URL is owned by any non-live workspace', function (): void {
    if (! Schema::hasColumn('page_urls', 'workspace_id')) {
        $this->markTestSkipped('Requires workspaces package (page_urls.workspace_id column)');
    }

    // Core has no concept of "caller workspace" — it flags any workspace_id != 0 as a workspace
    // conflict. Caller-workspace exclusion requires the workspaces-package detector.
    $site = Site::factory()->create();
    $uuid = (string) Str::uuid();

    DB::table('page_urls')->insert([
        'workspace_id' => 42,
        'site_id' => $site->getKey(),
        'language_id' => 1,
        'url' => '/own-workspace',
        'status' => 'draft',
        'pageable_type' => 'page',
        'pageable_id' => 7777,
        'type' => 'alias',
        'is_manual' => 0,
        'status_code' => 200,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $package = pageReviewPackage(
        sprintf('pages/%s.json', $uuid),
        pageReviewEnvelope($uuid, (int) $site->getKey(), '/own-workspace'),
    );

    $rows = (new BuildPageReviewRows)->run($package, resolvedSiteMap($site));

    expect($rows[0]->collisionState)->toBe(PageReviewRow::COLLISION_URL_WORKSPACE);
});
