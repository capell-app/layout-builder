<?php

declare(strict_types=1);

namespace Capell\Workspaces\Events;

use Capell\Core\Models\Version;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Emitted after a successful rollback. The event carries both the new
 * rollback-record Version row and the previous live version that was
 * demoted, so listeners can notify, log, or invalidate caches without
 * re-querying the versions table.
 */
class VersionRolledBack
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Version $version,
        public readonly Version $rolledBackTo,
        public readonly ?Version $previousLiveVersion = null,
        public readonly ?Authenticatable $actor = null,
        public readonly ?string $reason = null,
    ) {}
}
