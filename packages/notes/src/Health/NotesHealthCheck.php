<?php

declare(strict_types=1);

namespace Capell\Notes\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class NotesHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
