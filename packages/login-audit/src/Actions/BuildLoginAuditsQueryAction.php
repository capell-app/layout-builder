<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Actions;

use Capell\Core\Models\Site;
use Capell\LoginAudit\Models\LoginAudit;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildLoginAuditsQueryAction
{
    use AsAction;

    public function handle(
        ?Site $site = null,
        int $hours = 24,
        int $limit = 20,
    ): Builder {
        // Site filter is a no-op: LoginAudit has no site_id column and there
        // is no user-site pivot table in core. The $site parameter is accepted for
        // future use when such a linkage is introduced.
        return LoginAudit::query()
            ->with('authenticatable')
            ->where('login_at', '>=', now()->subHours($hours))
            ->latest('login_at')
            ->limit($limit);
    }
}
