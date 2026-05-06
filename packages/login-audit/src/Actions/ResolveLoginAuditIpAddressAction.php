<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Actions;

use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;

final class ResolveLoginAuditIpAddressAction
{
    use AsAction;

    public function handle(Request $request): ?string
    {
        if (! resolve(ShouldTrackUserIpAddressesAction::class)->handle()) {
            return null;
        }

        if (config('login-audit.behind_cdn') !== false) {
            return (string) $request->server(config('login-audit.behind_cdn.http_header_field'));
        }

        return (string) $request->ip();
    }
}
