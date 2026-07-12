<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum LayoutPresetSyncRunStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Completed = 'completed';
    case CompletedWithConflicts = 'completed_with_conflicts';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
