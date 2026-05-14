<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class TranslationManagerHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
