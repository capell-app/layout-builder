<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Console\Commands;

use Capell\LayoutBuilder\Actions\WidgetSnapshots\PrunePublicWidgetSnapshotsAction;
use Illuminate\Console\Command;

final class PrunePublicWidgetSnapshotsCommand extends Command
{
    protected $signature = 'capell:widget-snapshots:prune';

    protected $description = 'Delete expired or revoked public widget snapshots';

    public function handle(): int
    {
        $count = PrunePublicWidgetSnapshotsAction::run();
        $this->components->info(sprintf('Pruned %d public widget snapshot(s).', is_int($count) ? $count : 0));

        return self::SUCCESS;
    }
}
