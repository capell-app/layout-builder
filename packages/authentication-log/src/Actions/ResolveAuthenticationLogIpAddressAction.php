<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Actions;

use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;

final class ResolveAuthenticationLogIpAddressAction
{
    use AsAction;

    public function handle(Request $request): ?string
    {
        if (! resolve(ShouldTrackUserIpAddressesAction::class)->handle()) {
            return null;
        }

        if (config('authentication-log.behind_cdn') !== false) {
            return (string) $request->server(config('authentication-log.behind_cdn.http_header_field'));
        }

        return (string) $request->ip();
    }
}
