<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class CampaignStudioHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
