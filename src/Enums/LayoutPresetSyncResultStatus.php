<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum LayoutPresetSyncResultStatus: string
{
    case Updated = 'updated';
    case Skipped = 'skipped';
    case Conflict = 'conflict';
    case AssetConflict = 'asset_conflict';
    case Detached = 'detached';
}
