<?php

declare(strict_types=1);

namespace Capell\SeoTools\Policies;

use Capell\SeoTools\Settings\AssistantSettings;

class AiCreatorPolicy
{
    public function __construct(private readonly AssistantSettings $settings) {}

    public function isEnabledFor(object $site): bool
    {
        $siteOverride = $site->ai_creator_enabled ?? null;

        if ($siteOverride !== null) {
            return (bool) $siteOverride;
        }

        return $this->settings->ai_creator;
    }
}
