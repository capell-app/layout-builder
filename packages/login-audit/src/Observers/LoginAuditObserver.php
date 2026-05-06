<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Observers;

use Capell\LoginAudit\Actions\ShouldTrackUserIpAddressesAction;
use Rappasoft\LaravelLoginAudit\Models\LoginAudit;

class LoginAuditObserver
{
    public function saving(LoginAudit $authenticationLog): void
    {
        if (resolve(ShouldTrackUserIpAddressesAction::class)->handle()) {
            return;
        }

        $authenticationLog->ip_address = null;
    }

    public function creating(LoginAudit $authenticationLog): void
    {
        $authenticationLog->last_seen_at = $authenticationLog->login_at;
    }

    public function updating(LoginAudit $authenticationLog): void
    {
        if ($authenticationLog->isDirty('logout_at')) {
            $authenticationLog->last_seen_at = $authenticationLog->logout_at;
        }
    }
}
