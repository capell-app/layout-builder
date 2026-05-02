<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Actions;

use Capell\AuthenticationLog\Settings\AuthenticationLogSettings;
use Illuminate\Support\Facades\Config;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class ApplyAuthenticationLogSettingsAction
{
    use AsAction;

    public function handle(): void
    {
        try {
            /** @var AuthenticationLogSettings $settings */
            $settings = resolve(AuthenticationLogSettings::class);
            $retentionDays = $settings->retention_days;
        } catch (Throwable) {
            return;
        }

        Config::set('authentication-log.purge', max(1, $retentionDays));
    }
}
