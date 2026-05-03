<?php

declare(strict_types=1);

use Capell\Backup\Actions\InstallBackupPermissionsAction;
use Capell\Backup\Data\PageReviewRow;
use Capell\Backup\Data\RelationResolveRow;
use Capell\Backup\Enums\ImportSessionStatus;
use Capell\Backup\Jobs\ExecuteImportPlanJob;
use Capell\Backup\Models\ImportSession;
use Capell\Backup\Support\ChecksumGenerator;
use Capell\Core\Models\Site;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Actions\Imports\AdvancePageImportToValidationAction;
use Capell\Workspaces\Actions\Imports\DispatchPageImportAction;
use Capell\Workspaces\Actions\Imports\RefreshPageImportStatusAction;
use Capell\Workspaces\Actions\Imports\StartPageImportAction;
use Capell\Workspaces\Data\Imports\PageImportDecisionData;
use Capell\Workspaces\Filament\Pages\ImportPagesPage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)
    ->group('page-import-actions');

function writeActionImportPackage(
    string $absolutePath,
    string $pageUuid,
    int $siteId,
    string $url,
    ?int $layoutId = null,
): void {
    $manifestJson = json_encode([
        'schema_version' => 1,
        'package_type' => 'page-export',
    ], JSON_THROW_ON_ERROR);

    $sharedRelations = [
        'site' => ['ref' => 'site:' . $siteId],
    ];

    if ($layoutId !== null) {
        $sharedRelations['layout'] = ['ref' => 'layout:' . $layoutId];
    }

    $pageJson = json_encode([
        'type' => 'page',
        'uuid' => $pageUuid,
        'id' => 123,
        'attributes' => ['title' => 'Action Imported Page'],
        'owned_relations' => [
            'page_urls' => [
                ['site_id' => $siteId, 'language_id' => 1, 'url' => $url],
            ],
        ],
        'shared_relations' => $sharedRelations,
    ], JSON_THROW_ON_ERROR);

    $integrityFiles = [
        'manifest.json' => ChecksumGenerator::forString($manifestJson),
        sprintf('pages/%s.json', $pageUuid) => ChecksumGenerator::forString($pageJson),
    ];

    if ($layoutId !== null) {
        $layoutDescriptorJson = json_encode([
            'ref' => 'layout:' . $layoutId,
            'fingerprint' => 'layout-' . $layoutId,
            'name' => 'Action Layout',
        ], JSON_THROW_ON_ERROR);

        $integrityFiles[sprintf('relations/layouts/%d.json', $layoutId)] = ChecksumGenerator::forString($layoutDescriptorJson);
    }

    $zipArchive = new ZipArchive;
    $zipArchive->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zipArchive->addFromString('manifest.json', $manifestJson);
    $zipArchive->addFromString('integrity.json', json_encode(['files' => $integrityFiles], JSON_THROW_ON_ERROR));
    $zipArchive->addFromString(sprintf('pages/%s.json', $pageUuid), $pageJson);

    if (isset($layoutDescriptorJson)) {
        $zipArchive->addFromString(sprintf('relations/layouts/%d.json', $layoutId), $layoutDescriptorJson);
    }

    $zipArchive->close();
}

function stageActionImportPackage(
    string $relativePath,
    string $pageUuid,
    int $siteId,
    string $url,
    ?int $layoutId = null,
): void {
    $absolutePath = Storage::disk('local')->path($relativePath);
    if (! is_dir(dirname($absolutePath))) {
        mkdir(dirname($absolutePath), 0777, true);
    }

    writeActionImportPackage($absolutePath, $pageUuid, $siteId, $url, $layoutId);
}

function startActionImportWizard(string $archiveName, string $workspaceName, ?int $layoutId = null): array
{
    $site = Site::factory()->create(['name' => 'Action Site']);
    $pageUuid = (string) Str::uuid();
    $relativePath = sprintf('exchanger/imports/%s', $archiveName);

    stageActionImportPackage(
        $relativePath,
        $pageUuid,
        (int) $site->getKey(),
        '/action-' . Str::random(8),
        $layoutId,
    );

    $state = StartPageImportAction::run([
        'archive' => $relativePath,
        'archive_filename' => $archiveName,
        'workspace_name' => $workspaceName,
    ]);

    return [$state, $pageUuid, $site];
}

function actionDecisionDataFromState(object $state, bool $canUpdateSharedRelations = true): PageImportDecisionData
{
    return new PageImportDecisionData(
        sessionId: $state->sessionId,
        reviewRows: $state->reviewRows,
        pageDecisions: $state->pageDecisions,
        resolveRows: $state->resolveRows,
        relationDecisions: $state->relationDecisions,
        canUpdateSharedRelations: $canUpdateSharedRelations,
    );
}

beforeEach(function (): void {
    Permission::findOrCreate('View:ImportPagesPage', 'web');
    InstallBackupPermissionsAction::run();
    test()->actingAsAdmin();
    auth()->user()->givePermissionTo('View:ImportPagesPage');
    Storage::fake('local');
    Queue::fake();
});

it('moves upload state to review state after parsing a package', function (): void {
    [$state, $pageUuid] = startActionImportWizard('action-review.zip', 'Action Review');

    expect($state->step)->toBe(ImportPagesPage::STEP_REVIEW)
        ->and($state->sessionId)->toBeInt()
        ->and($state->reviewRows[0]['uuid'] ?? null)->toBe($pageUuid)
        ->and($state->pageDecisions[$pageUuid]['action'] ?? null)->toBe(PageReviewRow::ACTION_CREATE);

    $session = ImportSession::query()->findOrFail($state->sessionId);
    expect($session->status)->toBe(ImportSessionStatus::Parsed);

    Queue::assertNotPushed(ExecuteImportPlanJob::class);
});

it('moves review state to resolve when shared relations need decisions', function (): void {
    [$state] = startActionImportWizard('action-resolve.zip', 'Action Resolve', 777);

    $nextState = AdvancePageImportToValidationAction::run(
        actionDecisionDataFromState($state),
        false,
    );

    expect($nextState->step)->toBe(ImportPagesPage::STEP_RESOLVE)
        ->and($nextState->resolveRows)->not->toBeEmpty();
});

it('moves resolve state to validate after decisions are sanitized and summarized', function (): void {
    [$state, $pageUuid] = startActionImportWizard('action-validate.zip', 'Action Validate', 888);

    $relationDecisions = $state->relationDecisions;
    $relationDecisions['layout:888'] = [
        'action' => RelationResolveRow::ACTION_CREATE_NEW,
        'notes' => '',
    ];

    $nextState = AdvancePageImportToValidationAction::run(
        new PageImportDecisionData(
            sessionId: $state->sessionId,
            reviewRows: $state->reviewRows,
            pageDecisions: $state->pageDecisions,
            resolveRows: $state->resolveRows,
            relationDecisions: $relationDecisions,
            canUpdateSharedRelations: true,
        ),
        true,
    );

    expect($nextState->step)->toBe(ImportPagesPage::STEP_VALIDATE)
        ->and($nextState->validationSummary['pages'] ?? null)->toBeArray()
        ->and($nextState->confirmationExpected)->toBe('Action Validate');

    $session = ImportSession::query()->findOrFail($state->sessionId);
    expect($session->status)->toBe(ImportSessionStatus::Validated)
        ->and($session->page_decisions[$pageUuid]['action'] ?? null)->toBe(PageReviewRow::ACTION_CREATE)
        ->and($session->relation_decisions['layout:888']['action'] ?? null)->toBe(RelationResolveRow::ACTION_CREATE_NEW)
        ->and($session->relation_decisions['layout:888'])->not->toHaveKey('notes');
});

it('moves validate state to executing and queues the import job', function (): void {
    [$state] = startActionImportWizard('action-dispatch.zip', 'Action Dispatch');

    $validatedState = AdvancePageImportToValidationAction::run(
        actionDecisionDataFromState($state),
        false,
    );

    $status = DispatchPageImportAction::run(
        sessionId: $validatedState->sessionId,
        validationSummary: $validatedState->validationSummary,
        confirmation: $validatedState->confirmationExpected,
        confirmationExpected: $validatedState->confirmationExpected,
    );

    expect($status->step)->toBe(ImportPagesPage::STEP_EXECUTING)
        ->and($status->sessionStatus)->toBe(ImportSessionStatus::Queued->value)
        ->and($status->targetWorkspaceId)->toBeInt();

    Queue::assertPushed(ExecuteImportPlanJob::class, 1);
});

it('moves executing state to completed when the session completes', function (): void {
    [$state] = startActionImportWizard('action-completed.zip', 'Action Completed');

    $session = ImportSession::query()->findOrFail($state->sessionId);
    $session->forceFill([
        'status' => ImportSessionStatus::Completed,
        'result_summary' => [
            'pages_imported' => 4,
            'relations_resolved' => 2,
        ],
    ])->save();

    $status = RefreshPageImportStatusAction::run($state->sessionId, null);

    expect($status->step)->toBe(ImportPagesPage::STEP_COMPLETED)
        ->and($status->sessionStatus)->toBe(ImportSessionStatus::Completed->value)
        ->and($status->resultSummary['pages_imported'] ?? null)->toBe(4);
});

it('moves executing state to failed when the session fails', function (): void {
    [$state] = startActionImportWizard('action-failed.zip', 'Action Failed');

    $session = ImportSession::query()->findOrFail($state->sessionId);
    $session->forceFill([
        'status' => ImportSessionStatus::Failed,
        'failure_reason' => 'import execution failed',
    ])->save();

    $status = RefreshPageImportStatusAction::run($state->sessionId, null);

    expect($status->step)->toBe(ImportPagesPage::STEP_FAILED)
        ->and($status->sessionStatus)->toBe(ImportSessionStatus::Failed->value)
        ->and($status->failureReason)->toBe('import execution failed');
});
