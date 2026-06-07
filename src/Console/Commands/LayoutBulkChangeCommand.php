<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Console\Commands;

use Capell\LayoutBuilder\Actions\BulkChanges\ApplyLayoutBulkChangeRunAction;
use Capell\LayoutBuilder\Actions\BulkChanges\PreviewLayoutBulkChangeAction;
use Capell\LayoutBuilder\Data\LayoutBulkChangeCriteriaData;
use Capell\LayoutBuilder\Data\LayoutBulkWidgetOperationData;
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JsonException;
use LogicException;
use Throwable;

final class LayoutBulkChangeCommand extends Command
{
    protected $signature = 'capell:layouts:bulk-change
        {--spec= : Path to a JSON spec containing criteria and operation}
        {--preview : Create and store a preview run}
        {--approve= : Approve and apply an existing preview run UUID}
        {--json : Output the result as JSON}';

    protected $description = 'Preview or approve guided bulk changes to stored layout containers.';

    public function handle(): int
    {
        try {
            if (is_string($this->option('approve')) && $this->option('approve') !== '') {
                return $this->approve((string) $this->option('approve'));
            }

            if ((bool) $this->option('preview')) {
                return $this->preview();
            }
        } catch (Throwable $throwable) {
            return $this->failCommand($throwable->getMessage());
        }

        return $this->failCommand('Pass --preview with --spec, or pass --approve=<run-uuid>.');
    }

    private function preview(): int
    {
        $payload = $this->specPayload();
        $run = PreviewLayoutBulkChangeAction::run(
            LayoutBulkChangeCriteriaData::fromPayload($this->payloadSection($payload, 'criteria')),
            LayoutBulkWidgetOperationData::fromPayload($this->payloadSection($payload, 'operation')),
        );

        $this->render(['uuid' => $run->uuid, 'status' => $run->status->value, 'summary' => $run->summary]);

        return $run->status->value === 'blocked' ? self::FAILURE : self::SUCCESS;
    }

    private function approve(string $uuid): int
    {
        $run = LayoutBulkChangeRun::query()->where('uuid', $uuid)->first();

        if (! $run instanceof LayoutBulkChangeRun) {
            throw new LogicException(sprintf('Bulk layout change run [%s] was not found.', $uuid));
        }

        $summary = ApplyLayoutBulkChangeRunAction::run($run);
        $run->refresh();
        $this->render(['uuid' => $run->uuid, 'status' => $run->status->value, 'summary' => $summary]);

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function specPayload(): array
    {
        $path = $this->option('spec');

        if (! is_string($path) || $path === '') {
            throw new LogicException('The --spec option is required when previewing a bulk layout change.');
        }

        if (! File::exists($path)) {
            throw new LogicException(sprintf('Spec file [%s] does not exist.', $path));
        }

        try {
            $payload = json_decode((string) File::get($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new LogicException('Spec file must contain valid JSON: ' . $exception->getMessage(), previous: $exception);
        }

        if (! is_array($payload)) {
            throw new LogicException('Spec file must decode to a JSON object.');
        }

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    private function payloadSection(array $payload, string $key): array
    {
        $section = $payload[$key] ?? null;

        if (! is_array($section)) {
            throw new LogicException(sprintf('Spec file must contain a [%s] object.', $key));
        }

        return $section;
    }

    /** @param array<string, mixed> $payload */
    private function render(array $payload): void
    {
        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return;
        }

        $this->info(sprintf('Bulk layout change [%s] is %s.', $payload['uuid'], $payload['status']));

        foreach (($payload['summary'] ?? []) as $key => $value) {
            if (is_scalar($value)) {
                $this->line(sprintf('  %s: %s', $key, (string) $value));
            }
        }
    }

    private function failCommand(string $message): int
    {
        if ($this->option('json')) {
            $this->line(json_encode(['status' => 'failed', 'message' => $message], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } else {
            $this->error($message);
        }

        return self::FAILURE;
    }
}
