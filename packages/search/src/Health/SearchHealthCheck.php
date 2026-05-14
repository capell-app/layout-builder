<?php

declare(strict_types=1);

namespace Capell\Search\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class SearchHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
