<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Manifest;

use Capell\Core\Contracts\Extensions\RunsScheduledExtensionJob;

final class LayoutBuilderSnapshotPruneScheduleContribution implements RunsScheduledExtensionJob
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
