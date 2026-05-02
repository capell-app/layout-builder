<?php

declare(strict_types=1);

use Capell\Backup\Enums\ImportSessionKind;
use Capell\Backup\Enums\ImportSessionStatus;
use Capell\Backup\Jobs\ExecuteImportPlanJob;
use Capell\Backup\Models\ImportSession;
use Capell\Backup\Services\Import\MediaIngestService;
use Capell\Backup\Services\Import\PackageReader;
use Capell\Backup\Services\Import\PageImportService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

it('dispatches on the configured backup queue', function (): void {
    Queue::fake();

    dispatch(new ExecuteImportPlanJob(42));

    $queueName = config('backup.queue.name');
    Queue::assertPushedOn(
        is_string($queueName) ? $queueName : 'backup',
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
