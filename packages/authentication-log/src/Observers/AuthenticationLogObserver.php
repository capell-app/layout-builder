<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Observers;

use Capell\AuthenticationLog\Actions\ShouldTrackUserIpAddressesAction;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

class AuthenticationLogObserver
{
    public function saving(AuthenticationLog $authenticationLog): void
    {
        if (resolve(ShouldTrackUserIpAddressesAction::class)->handle()) {
            return;
        }

        $authenticationLog->ip_address = null;
    }

    public function creating(AuthenticationLog $authenticationLog): void
    {
        $authenticationLog->last_seen_at = $authenticationLog->login_at;
    }

    public function updating(AuthenticationLog $authenticationLog): void
    {
        if ($authenticationLog->isDirty('logout_at')) {
            $authenticationLog->last_seen_at = $authenticationLog->logout_at;
        }
    }
}
