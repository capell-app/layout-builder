<?php

declare(strict_types=1);

namespace Capell\Newsletter\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class NewsletterHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
