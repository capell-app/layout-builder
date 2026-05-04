<?php

declare(strict_types=1);

use Capell\Migrator\Enums\ImportSessionKind;
use Capell\Migrator\Enums\ImportSessionStatus;
use Capell\Migrator\Jobs\ExecuteImportPlanJob;
use Capell\Migrator\Models\ImportSession;
use Capell\Migrator\Services\Import\MediaIngestService;
use Capell\Migrator\Services\Import\PackageReader;
use Capell\Migrator\Services\Import\PageImportService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

it('dispatches on the configured migrator queue', function (): void {
    Queue::fake();

    dispatch(new ExecuteImportPlanJob(42));

    $queueName = config('migrator.queue.name');
    Queue::assertPushedOn(
        is_string($queueName) ? $queueName : 'migrator',
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
