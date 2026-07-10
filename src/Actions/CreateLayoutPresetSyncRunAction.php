<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Enums\LayoutPresetMode;
use Capell\LayoutBuilder\Enums\LayoutPresetSyncRunStatus;
use Capell\LayoutBuilder\Jobs\SyncLinkedLayoutPresetJob;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Models\LayoutPresetSyncRun;
use Capell\LayoutBuilder\Models\LayoutPresetUsage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use LogicException;
use Lorisleiva\Actions\Concerns\AsObject;

final class CreateLayoutPresetSyncRunAction
{
    use AsObject;

    public function handle(LayoutPreset $preset, ?LayoutPresetUsage $excludedUsage = null, ?int $initiatedBy = null): LayoutPresetSyncRun
    {
        return DB::transaction(function () use ($preset, $excludedUsage, $initiatedBy): LayoutPresetSyncRun {
            $lockedPreset = LayoutPreset::query()->lockForUpdate()->find($preset->getKey());
            throw_unless($lockedPreset instanceof LayoutPreset && $lockedPreset->mode === LayoutPresetMode::Linked, LogicException::class, 'Only linked layout presets can be synchronized.');

            $run = LayoutPresetSyncRun::query()->firstOrCreate(
                [
                    'preset_id' => $lockedPreset->getKey(),
                    'revision' => $lockedPreset->revision,
                ],
                [
                    'initiated_by' => $initiatedBy,
                    'excluded_usage_id' => $excludedUsage?->getKey(),
                    'status' => LayoutPresetSyncRunStatus::Queued,
                    'summary' => [],
                    'queued_at' => Date::now(),
                ],
            );

            SyncLinkedLayoutPresetJob::dispatch((int) $run->getKey())->afterCommit();

            return $run;
        });
    }
}
