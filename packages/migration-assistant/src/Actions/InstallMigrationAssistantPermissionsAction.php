<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Permission\Models\Permission;

/**
 * Idempotently install the migration-assistant permission matrix described in
 * §6.9 of the recovery-center plan. Running it repeatedly is safe -
 * existing rows are located via `firstOrCreate`.
 *
 * The permission names are the stable public contract; roles are left
 * to the application seeder so each install can map them onto its own
 * tiering.
 */
class InstallMigrationAssistantPermissionsAction
{
    use AsAction;

    /** @var string */
    public const PERMISSION_PAGE_EXPORT = 'page.export';

    /** @var string */
    public const PERMISSION_SITE_EXPORT = 'site.export';

    /** @var string */
    public const PERMISSION_PAGE_IMPORT = 'page.import';

    /** @var string */
    public const PERMISSION_SITE_IMPORT = 'site.import';

    /** @var string */
    public const PERMISSION_PAGE_IMPORT_UPDATE_SHARED = 'page.import.update-shared-relations';

    /** @var string */
    public const PERMISSION_PAGE_IMPORT_PUBLISH_LIVE = 'page.import.publish-live';

    /** @var string */
    public const PERMISSION_IMPORT_SESSION_VIEW = 'import-session.view';

    /** @var string */
    public const PERMISSION_IMPORT_SESSION_CANCEL = 'import-session.cancel';

    /** @var string */
    public const PERMISSION_IMPORT_SESSION_RETRY = 'import-session.retry';

    /**
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        return [
            self::PERMISSION_PAGE_EXPORT,
            self::PERMISSION_SITE_EXPORT,
            self::PERMISSION_PAGE_IMPORT,
            self::PERMISSION_SITE_IMPORT,
            self::PERMISSION_PAGE_IMPORT_UPDATE_SHARED,
            self::PERMISSION_PAGE_IMPORT_PUBLISH_LIVE,
            self::PERMISSION_IMPORT_SESSION_VIEW,
            self::PERMISSION_IMPORT_SESSION_CANCEL,
            self::PERMISSION_IMPORT_SESSION_RETRY,
        ];
    }

    public function handle(string $guardName = 'web'): void
    {
        foreach (self::permissionNames() as $name) {
            Permission::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => $guardName,
            ]);
        }
    }
}
