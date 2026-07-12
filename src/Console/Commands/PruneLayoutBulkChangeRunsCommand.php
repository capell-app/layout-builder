<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Console\Commands;

use Capell\LayoutBuilder\Actions\PruneLayoutBulkChangeRunsAction;
use Illuminate\Console\Command;

final class PruneLayoutBulkChangeRunsCommand extends Command
{
    protected $signature = 'capell:layout-builder:prune-bulk-change-runs';

    protected $description = 'Prune expired Layout Builder bulk-change snapshots.';

    public function handle(): int
    {
        $count = PruneLayoutBulkChangeRunsAction::run();
        $this->components->info((string) __('capell-layout-builder::generic.bulk_change_runs_pruned', ['count' => $count]));

        return self::SUCCESS;
    }
}
