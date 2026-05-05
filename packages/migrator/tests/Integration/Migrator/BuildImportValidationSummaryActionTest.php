<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Migrator\Actions\BuildImportValidationSummaryAction;
use Capell\Migrator\Data\PageReviewRow;
use Capell\Migrator\Data\RelationResolveRow;
use Capell\Migrator\Services\Import\PackageReadResult;
use Capell\Migrator\Services\Import\ResolutionMap;
use Capell\Migrator\Services\Import\Resolvers\MatchResolution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * @param  array<string, mixed>  $overrides
 */
function validationEnvelope(string $uuid, int $siteId, string $url, array $overrides = []): string
{
    return (string) json_encode(array_merge([
        'type' => 'page',
        'uuid' => $uuid,
        'attributes' => ['title' => 'Imported'],
        'owned_relations' => [
            'page_urls' => [
                ['site_id' => $siteId, 'language_id' => 1, 'url' => $url],
            ],
        ],
        'shared_relations' => [
            'site' => ['ref' => 'site:' . $siteId],
        ],
    ], $overrides));
}

function validationPackage(string $uuid, int $siteId, string $url): PackageReadResult
{
    return new PackageReadResult(
        archivePath: '',
        manifest: [],
        integrity: [],
        payload: [sprintf('pages/%s.json', $uuid) => validationEnvelope($uuid, $siteId, $url)],
    );
}

function siteResolvedMap(Site $site): ResolutionMap
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

it('produces clean counts for a basic package', function (): void {
    $site = Site::factory()->create();
    $uuid = (string) Str::uuid();

    $summary = (new BuildImportValidationSummaryAction)->run(
        package: validationPackage($uuid, (int) $site->getKey(), '/fresh'),
        map: siteResolvedMap($site),
        pageDecisions: [$uuid => ['action' => PageReviewRow::ACTION_CREATE]],
        relationDecisions: [
            'site:' . $site->getKey() => [
                'action' => RelationResolveRow::ACTION_USE_EXISTING,
                'target_id' => $site->getKey(),
            ],
        ],
    );

    expect($summary->pages)->toBe(['create' => 1, 'update' => 0, 'skip' => 0])
        ->and($summary->relations['match'])->toBe(1)
        ->and($summary->blockingErrors)->toBe([])
        ->and($summary->isClean())->toBeTrue();
});

it('flags a blocking error when a workspace-conflicted page is set to create', function (): void {
    if (! Schema::hasColumn('page_urls', 'workspace_id')) {
        $this->markTestSkipped('Requires workspaces package (page_urls.workspace_id column)');
    }

    $site = Site::factory()->create();
    $uuid = (string) Str::uuid();

    DB::table('page_urls')->insert([
        'workspace_id' => 77,
        'site_id' => $site->getKey(),
        'language_id' => 1,
        'url' => '/claimed',
        'status' => 'draft',
        'pageable_type' => 'page',
        'pageable_id' => 9999,
        'type' => 'alias',
        'is_manual' => 0,
        'status_code' => 200,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $summary = (new BuildImportValidationSummaryAction)->run(
        package: validationPackage($uuid, (int) $site->getKey(), '/claimed'),
        map: siteResolvedMap($site),
        pageDecisions: [$uuid => ['action' => PageReviewRow::ACTION_CREATE]],
        relationDecisions: [
            'site:' . $site->getKey() => ['action' => RelationResolveRow::ACTION_USE_EXISTING, 'target_id' => $site->getKey()],
        ],
    );

    expect($summary->blockingErrors)->not->toBeEmpty()
        ->and($summary->isClean())->toBeFalse();
});

it('warns when the resolver surfaces low-confidence alternatives', function (): void {
    $site = Site::factory()->create();
    $uuid = (string) Str::uuid();

    $siteRef = 'site:' . $site->getKey();
    $map = new ResolutionMap(
        resolved: [
            $siteRef => new MatchResolution(
                localId: (int) $site->getKey(),
                strategy: 'slug',
                confidence: 0.9,
                alternatives: [new MatchResolution(localId: 9999, strategy: 'slug', confidence: 0.3)],
            ),
        ],
        unresolved: [],
    );

    $summary = (new BuildImportValidationSummaryAction)->run(
        package: validationPackage($uuid, (int) $site->getKey(), '/low-conf'),
        map: $map,
        pageDecisions: [$uuid => ['action' => PageReviewRow::ACTION_CREATE]],
        relationDecisions: [$siteRef => ['action' => RelationResolveRow::ACTION_USE_EXISTING, 'target_id' => $site->getKey()]],
    );

    expect($summary->warnings)->not->toBeEmpty();
});

it('buckets media into import vs reuse based on resolution', function (): void {
    $site = Site::factory()->create();
    $uuid = (string) Str::uuid();

    $siteRef = 'site:' . $site->getKey();
    $resolvedMediaRef = 'media:100';
    $unresolvedMediaRef = 'media:200';

    $package = new PackageReadResult(
        archivePath: '',
        manifest: [],
        integrity: [],
        payload: [
            sprintf('pages/%s.json', $uuid) => validationEnvelope($uuid, (int) $site->getKey(), '/media'),
            'relations/media/100.json' => (string) json_encode(['ref' => $resolvedMediaRef]),
            'relations/media/200.json' => (string) json_encode(['ref' => $unresolvedMediaRef]),
        ],
    );

    $map = new ResolutionMap(
        resolved: [
            $siteRef => new MatchResolution(localId: (int) $site->getKey(), strategy: 'slug'),
            $resolvedMediaRef => new MatchResolution(localId: 500, strategy: 'checksum'),
        ],
        unresolved: [$unresolvedMediaRef],
    );

    $summary = (new BuildImportValidationSummaryAction)->run(
        package: $package,
        map: $map,
        pageDecisions: [$uuid => ['action' => PageReviewRow::ACTION_CREATE]],
        relationDecisions: [
            $siteRef => ['action' => RelationResolveRow::ACTION_USE_EXISTING, 'target_id' => $site->getKey()],
            $resolvedMediaRef => ['action' => RelationResolveRow::ACTION_USE_EXISTING, 'target_id' => 500],
            $unresolvedMediaRef => ['action' => RelationResolveRow::ACTION_CREATE_NEW],
        ],
    );

    expect($summary->media)->toBe(['import' => 1, 'reuse' => 1]);
});

it('treats an unresolved ref with use_existing as a blocking error', function (): void {
    $site = Site::factory()->create();
    $uuid = (string) Str::uuid();

    $map = new ResolutionMap(
        resolved: [
            'site:' . $site->getKey() => new MatchResolution(localId: (int) $site->getKey(), strategy: 'slug'),
        ],
        unresolved: ['layout:999'],
    );

    $summary = (new BuildImportValidationSummaryAction)->run(
        package: validationPackage($uuid, (int) $site->getKey(), '/unresolved'),
        map: $map,
        pageDecisions: [$uuid => ['action' => PageReviewRow::ACTION_CREATE]],
        relationDecisions: [
            'site:' . $site->getKey() => ['action' => RelationResolveRow::ACTION_USE_EXISTING, 'target_id' => $site->getKey()],
            'layout:999' => ['action' => RelationResolveRow::ACTION_USE_EXISTING, 'target_id' => null],
        ],
    );

    expect($summary->blockingErrors)->not->toBeEmpty();
});
