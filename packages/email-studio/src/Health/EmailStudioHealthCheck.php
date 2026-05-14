<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class EmailStudioHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
