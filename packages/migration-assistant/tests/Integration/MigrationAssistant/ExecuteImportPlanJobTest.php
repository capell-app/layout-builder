<?php

declare(strict_types=1);

use Capell\MigrationAssistant\Enums\ImportSessionKind;
use Capell\MigrationAssistant\Enums\ImportSessionStatus;
use Capell\MigrationAssistant\Jobs\ExecuteImportPlanJob;
use Capell\MigrationAssistant\Models\ImportSession;
use Capell\MigrationAssistant\Services\Import\MediaIngestService;
use Capell\MigrationAssistant\Services\Import\PackageReader;
use Capell\MigrationAssistant\Services\Import\PageImportService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

it('dispatches on the configured migration-assistant queue', function (): void {
    Queue::fake();

    dispatch(new ExecuteImportPlanJob(42));

    $queueName = config('migration-assistant.queue.name');
    Queue::assertPushedOn(
        is_string($queueName) ? $queueName : 'migration-assistant',
        ExecuteImportPlanJob::class,
    );
});

it('marks the session failed when source path is empty', function (): void {
    $session = ImportSession::query()->create([
        'uuid' => (string) Str::uuid(),
        'kind' => ImportSessionKind::PageImport,
        'status' => ImportSessionStatus::Queued,
        'source_package_path' => '',
    ]);

    (new ExecuteImportPlanJob((int) $session->getKey()))->handle(
        resolve(PackageReader::class),
        resolve(PageImportService::class),
        resolve(MediaIngestService::class),
    );

    $session->refresh();
    expect($session->status)->toBe(ImportSessionStatus::Failed)
        ->and($session->failure_reason)->toContain('source package');
});
