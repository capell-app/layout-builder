<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Actions;

use Capell\AuthenticationLog\Models\AuthenticationLog;
use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildAuthenticationLogsQueryAction
{
    use AsAction;

    public function handle(
        ?Site $site = null,
        int $hours = 24,
        int $limit = 20,
    ): Builder {
        // Site filter is a no-op: AuthenticationLog has no site_id column and there
        // is no user-site pivot table in core. The $site parameter is accepted for
        // future use when such a linkage is introduced.
        return AuthenticationLog::query()
            ->with('authenticatable')
            ->where('login_at', '>=', now()->subHours($hours))
            ->latest('login_at')
            ->limit($limit);
    }
}
