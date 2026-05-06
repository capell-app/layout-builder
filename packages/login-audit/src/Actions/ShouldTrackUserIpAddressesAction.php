<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Actions;

use Capell\LoginAudit\Settings\LoginAuditSettings;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class ShouldTrackUserIpAddressesAction
{
    use AsAction;

    public function handle(): bool
    {
        try {
            /** @var LoginAuditSettings $settings */
            $settings = resolve(LoginAuditSettings::class);

            return $settings->track_user_ip_addresses;
        } catch (Throwable) {
            return true;
        }
    }
}
