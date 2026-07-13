<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Console\Commands;

use Capell\LayoutBuilder\Actions\CreateLayoutPresetSyncRunAction;
use Capell\LayoutBuilder\Enums\LayoutPresetMode;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Models\LayoutPresetSyncRun;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use UnexpectedValueException;

final class ResyncLayoutPresetCommand extends Command
{
    protected $signature = 'layout-builder:resync-preset {preset : Linked layout preset ID or key}';

    protected $description = 'Queue a recovery synchronization for a linked layout preset.';

    public function handle(): int
    {
        $argument = $this->argument('preset');
        $identifier = is_string($argument) || is_int($argument) ? (string) $argument : '';
        $preset = LayoutPreset::query()
            ->where('mode', LayoutPresetMode::Linked)
            ->where(static function (Builder $query) use ($identifier): void {
                $query->where('key', $identifier)->orWhere('id', $identifier);
            })
            ->first();

        if (! $preset instanceof LayoutPreset) {
            $this->components->error('Linked layout preset not found.');

            return self::FAILURE;
        }

        $run = CreateLayoutPresetSyncRunAction::run($preset);
        if (! $run instanceof LayoutPresetSyncRun) {
            throw new UnexpectedValueException('Expected a layout preset sync run.');
        }
        $this->components->info(sprintf('Queued preset sync run %s.', $run->uuid));

        return self::SUCCESS;
    }
}
