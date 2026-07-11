<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Manifest;

use Capell\Core\Contracts\Extensions\ExtensionContribution;
use Capell\Core\Contracts\Extensions\RunsScheduledExtensionJob;

final class LayoutBuilderBulkChangePruneScheduleContribution implements ExtensionContribution, RunsScheduledExtensionJob
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.1';
    }
}
