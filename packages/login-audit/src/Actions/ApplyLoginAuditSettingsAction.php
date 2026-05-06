<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Actions;

use Capell\LoginAudit\Settings\LoginAuditSettings;
use Illuminate\Support\Facades\Config;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class ApplyLoginAuditSettingsAction
{
    use AsAction;

    public function handle(): void
    {
        try {
            /** @var LoginAuditSettings $settings */
            $settings = resolve(LoginAuditSettings::class);
            $retentionDays = $settings->retention_days;
        } catch (Throwable) {
            return;
        }

        Config::set('login-audit.purge', max(1, $retentionDays));
    }
}
