<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Manifest;

use Capell\Core\Contracts\Extensions\ExtensionContribution;

final class PublishingWorkflowPageContribution implements ExtensionContribution
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
