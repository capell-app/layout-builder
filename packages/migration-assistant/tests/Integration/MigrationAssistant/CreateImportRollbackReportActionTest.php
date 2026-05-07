<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\MigrationAssistant\Actions\CreateImportRollbackReportAction;
use Capell\MigrationAssistant\Enums\ImportSessionKind;
use Capell\MigrationAssistant\Enums\ImportSessionStatus;
use Capell\MigrationAssistant\Models\ImportSession;
use Capell\MigrationAssistant\Services\Import\ImportExecutionReport;
use Illuminate\Support\Str;

it('uses mysql-safe foreign key names for import rollback reports', function (): void {
    $migration = file_get_contents(dirname(__DIR__, 3) . '/database/migrations/02_create_import_rollback_dashboard-dashboard_reports_table.php');

    expect($migration)
        ->toContain("indexName: 'import_rollback_reports_session_fk'")
        ->toContain("indexName: 'import_rollback_reports_user_fk'")
        ->toContain("'import_rollback_reports_session_executed_idx'")
        ->and(strlen('import_rollback_reports_session_fk'))->toBeLessThanOrEqual(64)
        ->and(strlen('import_rollback_reports_user_fk'))->toBeLessThanOrEqual(64)
        ->and(strlen('import_rollback_reports_session_executed_idx'))->toBeLessThanOrEqual(64);
});

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
