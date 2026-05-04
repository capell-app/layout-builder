<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Migrator\Actions\InstallMigratorPermissionsAction;
use Capell\Migrator\Data\RelationResolveRow;
use Capell\Migrator\Jobs\ExecuteImportPlanJob;
use Capell\Migrator\Models\ImportSession;
use Capell\Migrator\Support\ChecksumGenerator;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Filament\Pages\ImportPagesPage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)
    ->group('import-pages-page-resolve');

/**
 * Build a package with an extra layouts relation descriptor so the
 * resolution map surfaces a layout ref alongside the site ref.
 */
function writeImportPackageWithLayoutRef(string $absolutePath, string $pageUuid, int $siteId, int $layoutId, string $url): void
{
    $manifestJson = json_encode([
        'schema_version' => 1,
        'package_type' => 'page-export',
    ], JSON_THROW_ON_ERROR);

    $pageJson = json_encode([
        'type' => 'page',
        'uuid' => $pageUuid,
        'id' => 321,
        'attributes' => ['title' => 'Imported With Layout'],
        'owned_relations' => [
            'page_urls' => [
                ['site_id' => $siteId, 'language_id' => 1, 'url' => $url],
            ],
        ],
        'shared_relations' => [
            'site' => ['ref' => 'site:' . $siteId],
            'layout' => ['ref' => 'layout:' . $layoutId],
        ],
    ], JSON_THROW_ON_ERROR);

    $layoutDescriptorJson = json_encode([
        'ref' => 'layout:' . $layoutId,
        'fingerprint' => 'abc-layout',
        'name' => 'Primary',
    ], JSON_THROW_ON_ERROR);

    $integrity = ['files' => [
        'manifest.json' => ChecksumGenerator::forString($manifestJson),
        sprintf('pages/%s.json', $pageUuid) => ChecksumGenerator::forString($pageJson),
        sprintf('relations/layouts/%d.json', $layoutId) => ChecksumGenerator::forString($layoutDescriptorJson),
    ]];

    $zip = new ZipArchive;
    $zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('manifest.json', $manifestJson);
    $zip->addFromString('integrity.json', json_encode($integrity, JSON_THROW_ON_ERROR));
    $zip->addFromString(sprintf('pages/%s.json', $pageUuid), $pageJson);
    $zip->addFromString(sprintf('relations/layouts/%d.json', $layoutId), $layoutDescriptorJson);
    $zip->close();
}

function writeBasicImportPackage(string $absolutePath, string $uuid, int $siteId, string $url): void
{
    $manifestJson = json_encode([
        'schema_version' => 1,
        'package_type' => 'page-export',
    ], JSON_THROW_ON_ERROR);

    $pageJson = json_encode([
        'type' => 'page',
        'uuid' => $uuid,
        'id' => 123,
        'attributes' => ['title' => 'Imported Page'],
        'owned_relations' => [
            'page_urls' => [
                ['site_id' => $siteId, 'language_id' => 1, 'url' => $url],
            ],
        ],
        'shared_relations' => [
            'site' => ['ref' => 'site:' . $siteId],
        ],
    ], JSON_THROW_ON_ERROR);

    $integrity = ['files' => [
        'manifest.json' => ChecksumGenerator::forString($manifestJson),
        sprintf('pages/%s.json', $uuid) => ChecksumGenerator::forString($pageJson),
    ]];

    $zip = new ZipArchive;
    $zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('manifest.json', $manifestJson);
    $zip->addFromString('integrity.json', json_encode($integrity, JSON_THROW_ON_ERROR));
    $zip->addFromString(sprintf('pages/%s.json', $uuid), $pageJson);
    $zip->close();
}

function stageResolvePackage(string $relativePath, string $pageUuid, int $siteId, int $layoutId, string $url): void
{
    $absolutePath = Storage::disk('local')->path($relativePath);
    if (! is_dir(dirname($absolutePath))) {
        mkdir(dirname($absolutePath), 0777, true);
    }

    writeImportPackageWithLayoutRef($absolutePath, $pageUuid, $siteId, $layoutId, $url);
}

beforeEach(function (): void {
    Permission::findOrCreate('View:ImportPagesPage', 'web');
    InstallMigratorPermissionsAction::run();
    test()->actingAsAdmin();
    auth()->user()->givePermissionTo('View:ImportPagesPage');
    Storage::fake('local');
    Queue::fake();
});

it('skips resolve and lands on validate when the map is trivial', function (): void {
    $site = Site::factory()->create();
    $uuid = (string) Str::uuid();

    $relativePath = 'exchanger/imports/trivial.zip';
    $absolutePath = Storage::disk('local')->path($relativePath);
    if (! is_dir(dirname($absolutePath))) {
        mkdir(dirname($absolutePath), 0777, true);
    }

    writeBasicImportPackage($absolutePath, $uuid, (int) $site->getKey(), '/trivial-resolve');

    Livewire::test(ImportPagesPage::class)
        ->set('data.archive', $relativePath)
        ->set('data.archive_filename', 'trivial.zip')
        ->set('data.workspace_name', 'Trivial')
        ->call('parseAndAdvance')
        ->assertSet('step', ImportPagesPage::STEP_REVIEW)
        ->call('advanceToResolve')
        ->assertSet('step', ImportPagesPage::STEP_VALIDATE);

    Queue::assertNotPushed(ExecuteImportPlanJob::class);
});

it('transitions review → resolve when the map has unresolved refs', function (): void {
    $site = Site::factory()->create();
    $uuid = (string) Str::uuid();
    $layoutId = 777;

    stageResolvePackage('exchanger/imports/resolve.zip', $uuid, (int) $site->getKey(), $layoutId, '/resolve-step');

    $component = Livewire::test(ImportPagesPage::class)
        ->set('data.archive', 'exchanger/imports/resolve.zip')
        ->set('data.archive_filename', 'resolve.zip')
        ->set('data.workspace_name', 'Resolve test')
        ->call('parseAndAdvance')
        // Unresolved layout ref keeps the wizard on review after parse (warning surfaced).
        ->assertSet('step', ImportPagesPage::STEP_REVIEW);

    expect($component->get('resolveRows'))->not->toBeEmpty();
});

it('hides update_existing when the user lacks the permission', function (): void {
    test()->actingAsUser();

    expect((new ImportPagesPage)->canUpdateSharedRelations())->toBeFalse();
});

it('exposes update_existing once the permission is granted', function (): void {
    auth()->user()->givePermissionTo(InstallMigratorPermissionsAction::PERMISSION_PAGE_IMPORT_UPDATE_SHARED);

    expect((new ImportPagesPage)->canUpdateSharedRelations())->toBeTrue();
});

it('denies publish-live when the user lacks page.import.publish-live', function (): void {
    test()->actingAsUser();

    expect((new ImportPagesPage)->canPublishLive())->toBeFalse();
});

it('allows publish-live once page.import.publish-live is granted', function (): void {
    auth()->user()->givePermissionTo(InstallMigratorPermissionsAction::PERMISSION_PAGE_IMPORT_PUBLISH_LIVE);

    expect((new ImportPagesPage)->canPublishLive())->toBeTrue();
});

it('blocks dispatch when a relation decision is invalid and persists decisions when valid', function (): void {
    $site = Site::factory()->create();
    $uuid = (string) Str::uuid();

    $relativePath = 'exchanger/imports/relation-decisions.zip';
    $absolutePath = Storage::disk('local')->path($relativePath);
    if (! is_dir(dirname($absolutePath))) {
        mkdir(dirname($absolutePath), 0777, true);
    }

    writeBasicImportPackage($absolutePath, $uuid, (int) $site->getKey(), '/relation-decisions');

    $siteRef = 'site:' . $site->getKey();

    $component = Livewire::test(ImportPagesPage::class)
        ->set('data.archive', $relativePath)
        ->set('data.archive_filename', 'relation-decisions.zip')
        ->set('data.workspace_name', 'Relations')
        ->call('parseAndAdvance')
        ->assertSet('step', ImportPagesPage::STEP_REVIEW);

    // Force an invalid decision: use_existing with no target_id.
    $component->set('resolveRows', [
        [
            'group' => 'sites',
            'ref' => $siteRef,
            'top_match' => ['local_id' => $site->getKey(), 'strategy' => 'uuid', 'confidence' => 1.0, 'reason' => ''],
            'alternatives' => [['local_id' => 999, 'strategy' => 'slug', 'confidence' => 0.5, 'reason' => 'also plausible']],
            'warnings' => [],
            'suggested_action' => RelationResolveRow::ACTION_USE_EXISTING,
        ],
    ]);
    $component->set('relationDecisions', [
        $siteRef => ['action' => RelationResolveRow::ACTION_USE_EXISTING, 'target_id' => null],
    ]);

    $component->call('advanceToValidate')
        ->assertSet('step', ImportPagesPage::STEP_RESOLVE);

    Queue::assertNotPushed(ExecuteImportPlanJob::class);

    // Provide a valid decision and advance.
    $component->set('relationDecisions', [
        $siteRef => ['action' => RelationResolveRow::ACTION_USE_EXISTING, 'target_id' => $site->getKey()],
    ]);

    $component->call('advanceToValidate')
        ->assertSet('step', ImportPagesPage::STEP_VALIDATE);

    // Dispatch from validate requires confirmation.
    $component->set('confirmation', 'Relations');
    $component->call('dispatchImport')
        ->assertSet('step', ImportPagesPage::STEP_DISPATCHED);

    Queue::assertPushed(ExecuteImportPlanJob::class, 1);

    $session = ImportSession::query()->latest('id')->firstOrFail();
    expect($session->relation_decisions)->toBeArray()
        ->and($session->relation_decisions[$siteRef]['action'] ?? null)->toBe(RelationResolveRow::ACTION_USE_EXISTING)
        ->and($session->relation_decisions[$siteRef]['target_id'] ?? null)->toBe($site->getKey());
});
