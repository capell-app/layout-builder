<?php

declare(strict_types=1);

use Capell\Admin\Actions\InstallExchangerPermissionsAction;
use Capell\Core\Exchanger\Enums\ImportSessionStatus;
use Capell\Core\Exchanger\Jobs\ExecuteImportPlanJob;
use Capell\Core\Exchanger\Models\ImportSession;
use Capell\Core\Exchanger\Support\ChecksumGenerator;
use Capell\Core\Models\Site;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Filament\Pages\ImportPagesPage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)
    ->group('import-pages-page-execute');

function writeExecutePackage(string $absolutePath, string $uuid, int $siteId, string $url): void
{
    $manifestJson = json_encode([
        'schema_version' => 1,
        'package_type' => 'page-export',
    ], JSON_THROW_ON_ERROR);

    $pageJson = json_encode([
        'type' => 'page',
        'uuid' => $uuid,
        'id' => 123,
        'attributes' => ['title' => 'Execute Page'],
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

function stageExecutePackage(string $relativePath, string $uuid, int $siteId, string $url): void
{
    $absolutePath = Storage::disk('local')->path($relativePath);
    if (! is_dir(dirname($absolutePath))) {
        mkdir(dirname($absolutePath), 0777, true);
    }

    writeExecutePackage($absolutePath, $uuid, $siteId, $url);
}

beforeEach(function (): void {
    Permission::findOrCreate('View:ImportPagesPage', 'web');
    InstallExchangerPermissionsAction::run();
    test()->actingAsAdmin();
    auth()->user()->givePermissionTo('View:ImportPagesPage');
    Storage::fake('local');
    Queue::fake();
});

function bootExecuteWizardToDispatch(string $archiveName, string $workspaceName): Testable
{
    $site = Site::factory()->create(['name' => 'Execute Site']);
    $uuid = (string) Str::uuid();

    stageExecutePackage(
        sprintf('exchanger/imports/%s', $archiveName),
        $uuid,
        (int) $site->getKey(),
        '/execute-' . Str::random(6),
    );

    $component = Livewire::test(ImportPagesPage::class)
        ->set('data.archive', sprintf('exchanger/imports/%s', $archiveName))
        ->set('data.archive_filename', $archiveName)
        ->set('data.workspace_name', $workspaceName)
        ->call('parseAndAdvance')
        ->call('advanceToResolve')
        ->assertSet('step', ImportPagesPage::STEP_VALIDATE);

    $component->set('confirmation', $workspaceName);
    $component->call('dispatchImport')
        ->assertSet('step', ImportPagesPage::STEP_EXECUTING);

    Queue::assertPushed(ExecuteImportPlanJob::class, 1);

    return $component;
}

it('transitions to the executing step after dispatchImport with session id captured', function (): void {
    $component = bootExecuteWizardToDispatch('execute-basic.zip', 'Execute WS');

    expect($component->get('sessionId'))->toBeInt()
        ->and($component->get('sessionId'))->toBeGreaterThan(0)
        ->and($component->get('sessionStatus'))->toBe(ImportSessionStatus::Queued->value);

    $component->call('refreshStatus')
        ->assertSet('step', ImportPagesPage::STEP_EXECUTING);
});

it('transitions to completed step with result_summary surfaced', function (): void {
    $component = bootExecuteWizardToDispatch('execute-complete.zip', 'Complete WS');

    $sessionId = $component->get('sessionId');
    $session = ImportSession::query()->findOrFail($sessionId);
    $session->forceFill([
        'status' => ImportSessionStatus::Completed,
        'result_summary' => [
            'pages_imported' => 7,
            'relations_resolved' => 3,
            'media_ingested' => 2,
        ],
    ])->save();

    $component->call('refreshStatus')
        ->assertSet('step', ImportPagesPage::STEP_COMPLETED);

    expect($component->get('resultSummary'))->toMatchArray([
        'pages_imported' => 7,
        'relations_resolved' => 3,
        'media_ingested' => 2,
    ])->and($component->get('sessionStatus'))->toBe(ImportSessionStatus::Completed->value);
});

it('transitions to failed step when the session status is Failed', function (): void {
    $component = bootExecuteWizardToDispatch('execute-fail.zip', 'Failing WS');

    $sessionId = $component->get('sessionId');
    $session = ImportSession::query()->findOrFail($sessionId);
    $session->forceFill([
        'status' => ImportSessionStatus::Failed,
        'failure_reason' => 'something went wrong inside the job',
    ])->save();

    $component->call('refreshStatus')
        ->assertSet('step', ImportPagesPage::STEP_FAILED);

    expect($component->get('failureReason'))->toBe('something went wrong inside the job')
        ->and($component->get('sessionStatus'))->toBe(ImportSessionStatus::Failed->value);
});
