<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\MigrationAssistant\Actions\CreateImportRollbackReportAction;
use Capell\MigrationAssistant\Enums\ImportSessionKind;
use Capell\MigrationAssistant\Enums\ImportSessionStatus;
use Capell\MigrationAssistant\Models\ImportSession;
use Capell\MigrationAssistant\Services\Import\ImportExecutionReport;
use Illuminate\Support\Str;

it('creates an import rollback report from an execution report', function (): void {
    $session = ImportSession::query()->create([
        'uuid' => (string) Str::uuid(),
        'kind' => ImportSessionKind::PageImport,
        'status' => ImportSessionStatus::Completed,
        'source_filename' => 'pages.zip',
        'source_package_checksum' => 'sha256-example',
        'executed_at' => now(),
    ]);

    $report = new ImportExecutionReport(
        pagesCreated: 1,
        pagesSkipped: 0,
        createdPageIds: [123],
        errors: [],
        pageUrlsCreated: 2,
        mediaReassigned: 1,
    );

    $rollbackReport = CreateImportRollbackReportAction::run($session, $report);

    expect($rollbackReport->import_session_id)->toBe($session->getKey())
        ->and($rollbackReport->source_filename)->toBe('pages.zip')
        ->and($rollbackReport->created_models)->toBe([
            ['class' => Page::class, 'id' => 123],
        ])
        ->and($rollbackReport->summary['page_urls_created'])->toBe(2)
        ->and($rollbackReport->manual_instructions)->toContain('roll back');
});
