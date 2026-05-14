<?php

declare(strict_types=1);

namespace Capell\WordPressImporter\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class WordpressImporterHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
