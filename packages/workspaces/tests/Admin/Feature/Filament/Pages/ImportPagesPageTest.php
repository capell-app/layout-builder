<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Migrator\Data\PageReviewRow;
use Capell\Migrator\Jobs\ExecuteImportPlanJob;
use Capell\Migrator\Models\ImportSession;
use Capell\Migrator\Support\ChecksumGenerator;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Filament\Pages\ImportPagesPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)
    ->group('import-pages-page');

function writeImportPackage(string $absolutePath, string $uuid, int $siteId, string $url): void
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

beforeEach(function (): void {
    Permission::findOrCreate('View:ImportPagesPage', 'web');
    test()->actingAsAdmin();
    auth()->user()->givePermissionTo('View:ImportPagesPage');
    Storage::fake('local');
    Queue::fake();
});

it('transitions to review step after parsing a package', function (): void {
    $site = Site::factory()->create();
    $uuid = (string) Str::uuid();

    $relativePath = 'exchanger/imports/test-package.zip';
    $absolutePath = Storage::disk('local')->path($relativePath);
    if (! is_dir(dirname($absolutePath))) {
        mkdir(dirname($absolutePath), 0777, true);
    }

    writeImportPackage($absolutePath, $uuid, (int) $site->getKey(), '/hello-world');

    Livewire::test(ImportPagesPage::class)
        ->set('data.archive', $relativePath)
        ->set('data.archive_filename', 'test-package.zip')
        ->set('data.workspace_name', 'Import test')
        ->call('parseAndAdvance')
        ->assertSet('step', ImportPagesPage::STEP_REVIEW)
        ->assertSet('reviewRows.0.uuid', $uuid)
        ->assertSet(sprintf('pageDecisions.%s.action', $uuid), PageReviewRow::ACTION_CREATE);

    Queue::assertNotPushed(ExecuteImportPlanJob::class);
});

it('blocks dispatch when a workspace collision is not skipped', function (): void {
    $site = Site::factory()->create();
    $uuid = (string) Str::uuid();

    // Seed a different workspace claiming the URL.
    DB::table('page_urls')->insert([
        'workspace_id' => 999,
        'site_id' => $site->getKey(),
        'language_id' => 1,
        'url' => '/contested',
        'status' => 'draft',
        'pageable_type' => 'page',
        'pageable_id' => 42,
        'type' => 'alias',
        'is_manual' => 0,
        'status_code' => 200,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $relativePath = 'exchanger/imports/conflict.zip';
    $absolutePath = Storage::disk('local')->path($relativePath);
    if (! is_dir(dirname($absolutePath))) {
        mkdir(dirname($absolutePath), 0777, true);
    }

    writeImportPackage($absolutePath, $uuid, (int) $site->getKey(), '/contested');

    $component = Livewire::test(ImportPagesPage::class)
        ->set('data.archive', $relativePath)
        ->set('data.archive_filename', 'conflict.zip')
        ->set('data.workspace_name', 'Conflict test')
        ->call('parseAndAdvance')
        ->assertSet('step', ImportPagesPage::STEP_REVIEW)
        ->assertSet(sprintf('pageDecisions.%s.action', $uuid), PageReviewRow::ACTION_SKIP);

    // Default (suggested skip) should reach validate, then dispatch after confirmation.
    $component->call('advanceToResolve')
        ->assertSet('step', ImportPagesPage::STEP_VALIDATE);

    $component->set('confirmation', 'Conflict test');
    $component->call('dispatchImport')
        ->assertSet('step', ImportPagesPage::STEP_DISPATCHED);

    Queue::assertPushed(ExecuteImportPlanJob::class, 1);

    $session = ImportSession::query()->latest('id')->firstOrFail();
    expect($session->page_decisions)->toBeArray()
        ->and($session->page_decisions[$uuid]['action'] ?? null)->toBe(PageReviewRow::ACTION_SKIP);
});

it('rejects dispatch when user overrides a workspace conflict to create', function (): void {
    $site = Site::factory()->create();
    $uuid = (string) Str::uuid();

    DB::table('page_urls')->insert([
        'workspace_id' => 999,
        'site_id' => $site->getKey(),
        'language_id' => 1,
        'url' => '/contested-override',
        'status' => 'draft',
        'pageable_type' => 'page',
        'pageable_id' => 43,
        'type' => 'alias',
        'is_manual' => 0,
        'status_code' => 200,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $relativePath = 'exchanger/imports/override.zip';
    $absolutePath = Storage::disk('local')->path($relativePath);
    if (! is_dir(dirname($absolutePath))) {
        mkdir(dirname($absolutePath), 0777, true);
    }

    writeImportPackage($absolutePath, $uuid, (int) $site->getKey(), '/contested-override');

    Livewire::test(ImportPagesPage::class)
        ->set('data.archive', $relativePath)
        ->set('data.archive_filename', 'override.zip')
        ->set('data.workspace_name', 'Override test')
        ->call('parseAndAdvance')
        ->set(sprintf('pageDecisions.%s.action', $uuid), PageReviewRow::ACTION_CREATE)
        ->call('dispatchImport')
        ->assertSet('step', ImportPagesPage::STEP_REVIEW);

    Queue::assertNotPushed(ExecuteImportPlanJob::class);
});
