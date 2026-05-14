<?php

declare(strict_types=1);

namespace Capell\MediaLibrary\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class MediaLibraryHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
