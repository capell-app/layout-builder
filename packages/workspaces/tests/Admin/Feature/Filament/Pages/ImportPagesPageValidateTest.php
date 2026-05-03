<?php

declare(strict_types=1);

use Capell\Backup\Actions\InstallBackupPermissionsAction;
use Capell\Backup\Enums\ImportSessionStatus;
use Capell\Backup\Jobs\ExecuteImportPlanJob;
use Capell\Backup\Models\ImportSession;
use Capell\Backup\Support\ChecksumGenerator;
use Capell\Core\Models\Site;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Filament\Pages\ImportPagesPage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)
    ->group('import-pages-page-validate');

function writeValidatePackage(string $absolutePath, string $uuid, int $siteId, string $url): void
{
    $manifestJson = json_encode([
        'schema_version' => 1,
        'package_type' => 'page-export',
    ], JSON_THROW_ON_ERROR);

    $pageJson = json_encode([
        'type' => 'page',
        'uuid' => $uuid,
        'id' => 123,
        'attributes' => ['title' => 'Validate Page'],
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

function stageValidatePackage(string $relativePath, string $uuid, int $siteId, string $url): void
{
    $absolutePath = Storage::disk('local')->path($relativePath);
    if (! is_dir(dirname($absolutePath))) {
        mkdir(dirname($absolutePath), 0777, true);
    }

    writeValidatePackage($absolutePath, $uuid, $siteId, $url);
}

beforeEach(function (): void {
    Permission::findOrCreate('View:ImportPagesPage', 'web');
    InstallBackupPermissionsAction::run();
    test()->actingAsAdmin();
    auth()->user()->givePermissionTo('View:ImportPagesPage');
    Storage::fake('local');
    Queue::fake();
});

it('advances from review (trivial map) to validate and stores the summary', function (): void {
    $site = Site::factory()->create(['name' => 'Acme Site']);
    $uuid = (string) Str::uuid();

    stageValidatePackage('exchanger/imports/validate-basic.zip', $uuid, (int) $site->getKey(), '/validate-basic');

    $component = Livewire::test(ImportPagesPage::class)
        ->set('data.archive', 'exchanger/imports/validate-basic.zip')
        ->set('data.archive_filename', 'validate-basic.zip')
        ->set('data.workspace_name', 'Validate WS')
        ->call('parseAndAdvance')
        ->assertSet('step', ImportPagesPage::STEP_REVIEW)
        ->call('advanceToResolve')
        ->assertSet('step', ImportPagesPage::STEP_VALIDATE);

    expect($component->get('validationSummary'))->toBeArray()
        ->and($component->get('validationSummary')['pages'] ?? null)->toBeArray()
        ->and($component->get('confirmationExpected'))->toBe('Validate WS');

    $session = ImportSession::query()->latest('id')->firstOrFail();
    expect($session->status)->toBe(ImportSessionStatus::Validated)
        ->and($session->validation_results)->toBeArray()
        ->and($session->validation_results['pages'] ?? null)->toBeArray();
});

it('blocks dispatch without a matching confirmation string', function (): void {
    $site = Site::factory()->create(['name' => 'Beta Site']);
    $uuid = (string) Str::uuid();

    stageValidatePackage('exchanger/imports/validate-confirm.zip', $uuid, (int) $site->getKey(), '/validate-confirm');

    $component = Livewire::test(ImportPagesPage::class)
        ->set('data.archive', 'exchanger/imports/validate-confirm.zip')
        ->set('data.archive_filename', 'validate-confirm.zip')
        ->set('data.workspace_name', 'Confirm WS')
        ->call('parseAndAdvance')
        ->call('advanceToResolve')
        ->assertSet('step', ImportPagesPage::STEP_VALIDATE);

    // No confirmation typed
    $component->call('dispatchImport')
        ->assertSet('step', ImportPagesPage::STEP_VALIDATE);
    Queue::assertNotPushed(ExecuteImportPlanJob::class);

    // Wrong confirmation
    $component->set('confirmation', 'not the site');
    $component->call('dispatchImport')
        ->assertSet('step', ImportPagesPage::STEP_VALIDATE);
    Queue::assertNotPushed(ExecuteImportPlanJob::class);

    // Correct confirmation (workspace-name fallback, case-insensitive)
    $component->set('confirmation', 'confirm ws');
    $component->call('dispatchImport')
        ->assertSet('step', ImportPagesPage::STEP_DISPATCHED);
    Queue::assertPushed(ExecuteImportPlanJob::class, 1);
});

it('blocks dispatch while blocking_errors present and succeeds when clean', function (): void {
    $site = Site::factory()->create(['name' => 'Gamma Site']);
    $uuid = (string) Str::uuid();

    stageValidatePackage('exchanger/imports/validate-clean.zip', $uuid, (int) $site->getKey(), '/validate-clean');

    $component = Livewire::test(ImportPagesPage::class)
        ->set('data.archive', 'exchanger/imports/validate-clean.zip')
        ->set('data.archive_filename', 'validate-clean.zip')
        ->set('data.workspace_name', 'Clean WS')
        ->call('parseAndAdvance')
        ->call('advanceToResolve')
        ->assertSet('step', ImportPagesPage::STEP_VALIDATE);

    // Inject blocking errors on the persisted summary.
    $component->set('validationSummary', array_merge(
        $component->get('validationSummary'),
        ['blocking_errors' => ['forced blocker']],
    ));
    $component->set('confirmation', 'Clean WS');
    $component->call('dispatchImport')
        ->assertSet('step', ImportPagesPage::STEP_VALIDATE);
    Queue::assertNotPushed(ExecuteImportPlanJob::class);

    // Clear blocking_errors, dispatch cleanly.
    $component->set('validationSummary', array_merge(
        $component->get('validationSummary'),
        ['blocking_errors' => []],
    ));
    $component->call('dispatchImport')
        ->assertSet('step', ImportPagesPage::STEP_DISPATCHED);

    Queue::assertPushed(ExecuteImportPlanJob::class, 1);

    $session = ImportSession::query()->latest('id')->firstOrFail();
    expect($session->status)->toBe(ImportSessionStatus::Queued)
        ->and($session->validation_results)->toBeArray();
});
