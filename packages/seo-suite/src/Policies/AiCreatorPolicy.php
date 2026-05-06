<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Policies;

use Capell\SeoSuite\Settings\AIOrchestratorSettings;

class AiCreatorPolicy
{
    public function __construct(private readonly AIOrchestratorSettings $settings) {}

    public function isEnabledFor(object $site): bool
    {
        $siteOverride = $site->ai_creator_enabled ?? null;

        if ($siteOverride !== null) {
            return (bool) $siteOverride;
        }

        return $this->settings->ai_creator;
    }
}
