<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Actions;

use Capell\AuthenticationLog\Settings\AuthenticationLogSettings;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class ShouldTrackUserIpAddressesAction
{
    use AsAction;

    public function handle(): bool
    {
        try {
            /** @var AuthenticationLogSettings $settings */
            $settings = resolve(AuthenticationLogSettings::class);

            return $settings->track_user_ip_addresses;
        } catch (Throwable) {
            return true;
        }
    }
}
