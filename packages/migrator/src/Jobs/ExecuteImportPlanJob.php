<?php

declare(strict_types=1);

namespace Capell\Migrator\Jobs;

use Capell\Migrator\Actions\CreateImportRollbackReportAction;
use Capell\Migrator\Enums\ImportSessionStatus;
use Capell\Migrator\Events\ImportCompleted;
use Capell\Migrator\Events\ImportFailed;
use Capell\Migrator\Models\ImportSession;
use Capell\Migrator\Services\Import\MediaIngestService;
use Capell\Migrator\Services\Import\PackageReader;
use Capell\Migrator\Services\Import\PackageReadResult;
use Capell\Migrator\Services\Import\PageImportService;
use Capell\Migrator\Services\Import\ResolutionMap;
use Capell\Migrator\Services\Import\Resolvers\MatchResolution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Runs the page-import execute phase on the migrator queue. The
 * session has already been parsed and mapped by the wizard; this job
 * re-reads the archive, reassembles the ResolutionMap from the stored
 * decisions, and hands off to PageImportService. Status transitions
 * Queued → Running → Completed|Failed drive the Livewire progress bar.
 */
final class ExecuteImportPlanJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public int $importSessionId)
    {
        $queueName = config('migrator.queue.name', 'migrator');
        $this->onQueue(is_string($queueName) ? $queueName : 'migrator');

        $connection = config('migrator.queue.connection');
        if (is_string($connection) && $connection !== '') {
            $this->onConnection($connection);
        }
    }

    public function handle(PackageReader $reader, PageImportService $importer, MediaIngestService $mediaIngester): void
    {
        $session = ImportSession::query()->findOrFail($this->importSessionId);

        $archivePath = (string) $session->source_package_path;
        if ($archivePath === '') {
            $this->markFailed($session, 'Import session has no source package path.');

            return;
        }

        $session->forceFill([
            'status' => ImportSessionStatus::Running,
        ])->save();

        try {
            $disk = config('migrator.disk', 'local');
            $absolutePath = Storage::disk(is_string($disk) ? $disk : 'local')->path($archivePath);
            $package = $reader->read($absolutePath);
            $map = $this->hydrateResolutionMap($session);
            $map = $this->ingestMediaBinaries($package, $map, $mediaIngester, $session);

            throw_if(
                $map->hasUnresolved(),
                RuntimeException::class,
                'Refusing to execute import with unresolved references.',
            );

            $report = $importer->import($package, $map);

            $failureReason = $report->isSuccess() ? null : implode(' / ', array_slice($report->errors, 0, 5));

            $session->forceFill([
                'result_summary' => $report->toArray(),
                'status' => $report->isSuccess() ? ImportSessionStatus::Completed : ImportSessionStatus::Failed,
                'failure_reason' => $failureReason,
                'executed_at' => now(),
            ])->save();

            if ($report->isSuccess()) {
                CreateImportRollbackReportAction::run($session, $report);
                event(new ImportCompleted($session));
            } else {
                event(new ImportFailed($session, (string) $failureReason));
            }
        } catch (Throwable $throwable) {
            $this->markFailed($session, $throwable->getMessage());

            throw $throwable;
        }
    }

    /**
     * Scan relations/media/*.json descriptors in the package and ingest any
     * binary whose ref is still unresolved — the resulting local Media id is
     * folded into the resolution map so PageImportService::rebindMedia() can
     * point freshly-created Media rows at the imported Pages in the second
     * pass. Runs outside the import transaction; MediaIngestService keys
     * idempotency on the sha256 checksum, so a retried job is safe.
     */
    private function ingestMediaBinaries(
        PackageReadResult $package,
        ResolutionMap $map,
        MediaIngestService $mediaIngester,
        ImportSession $session,
    ): ResolutionMap {
        $resolved = $map->resolved;
        $unresolved = $map->unresolved;

        foreach ($package->payload as $entryPath => $contents) {
            if (! str_starts_with($entryPath, 'relations/media/')) {
                continue;
            }

            /** @var array<string, mixed> $descriptor */
            $descriptor = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            $ref = $descriptor['ref'] ?? null;
            if (! is_string($ref)) {
                continue;
            }

            if (array_key_exists($ref, $resolved)) {
                continue;
            }

            $localId = $mediaIngester->ingest($package->archivePath, $descriptor, $session);
            $resolved[$ref] = new MatchResolution(
                localId: $localId,
                strategy: 'ingest.checksum',
                confidence: 1.0,
            );
            $unresolved = array_values(array_filter($unresolved, static fn (string $value): bool => $value !== $ref));
        }

        return new ResolutionMap(resolved: $resolved, unresolved: $unresolved);
    }

    private function hydrateResolutionMap(ImportSession $session): ResolutionMap
    {
        $raw = is_array($session->resolution_map) ? $session->resolution_map : [];
        $resolvedRaw = is_array($raw['resolved'] ?? null) ? $raw['resolved'] : [];
        $unresolved = is_array($raw['unresolved'] ?? null) ? array_values(array_filter($raw['unresolved'], is_string(...))) : [];

        $resolved = [];
        foreach ($resolvedRaw as $ref => $entry) {
            if (! is_string($ref)) {
                continue;
            }

            if (! is_array($entry)) {
                continue;
            }

            $resolution = $this->decodeResolution($entry);
            if (! $resolution instanceof MatchResolution) {
                continue;
            }

            $resolved[$ref] = $resolution;
        }

        return new ResolutionMap(resolved: $resolved, unresolved: $unresolved);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function decodeResolution(array $entry): ?MatchResolution
    {
        $localId = $entry['local_id'] ?? null;
        if (! is_int($localId) && ! is_string($localId)) {
            return null;
        }

        $alternativesRaw = is_array($entry['alternatives'] ?? null) ? $entry['alternatives'] : [];
        $alternatives = [];
        foreach ($alternativesRaw as $alternativeEntry) {
            if (! is_array($alternativeEntry)) {
                continue;
            }

            $alternative = $this->decodeResolution($alternativeEntry);
            if ($alternative instanceof MatchResolution) {
                $alternatives[] = $alternative;
            }
        }

        return new MatchResolution(
            localId: $localId,
            strategy: is_string($entry['strategy'] ?? null) ? $entry['strategy'] : 'unknown',
            confidence: is_numeric($entry['confidence'] ?? null) ? (float) $entry['confidence'] : 1.0,
            reason: is_string($entry['reason'] ?? null) ? $entry['reason'] : '',
            alternatives: $alternatives,
        );
    }

    private function markFailed(ImportSession $session, string $reason): void
    {
        $session->forceFill([
            'status' => ImportSessionStatus::Failed,
            'failure_reason' => $reason,
        ])->save();

        event(new ImportFailed($session, $reason));
    }
}
