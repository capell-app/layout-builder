<?php

declare(strict_types=1);

namespace Capell\Backup\Providers;

use Capell\Admin\Contracts\Backup\PageExporter;
use Capell\Admin\Support\CapellAdminManager;
use Capell\Backup\Contracts\BackupContextResolver;
use Capell\Backup\Contracts\BackupRowContributor;
use Capell\Backup\Contracts\NullBackupContextResolver;
use Capell\Backup\Contracts\NullBackupRowContributor;
use Capell\Backup\Contracts\NullPageCollisionDetector;
use Capell\Backup\Contracts\PageCollisionDetector;
use Capell\Backup\Events\ImportCompleted;
use Capell\Backup\Events\ImportFailed;
use Capell\Backup\Filament\Pages\ImportSitesPage;
use Capell\Backup\Filament\Resources\ImportSessions\ImportSessionResource;
use Capell\Backup\Listeners\SendImportSessionNotifications;
use Capell\Backup\Models\BackupRestore;
use Capell\Backup\Models\ImportSession;
use Capell\Backup\Policies\ImportSessionPolicy;
use Capell\Backup\Services\Import\Resolvers\FingerprintMatchResolver;
use Capell\Backup\Services\Import\Resolvers\KeyedMatchResolver;
use Capell\Backup\Services\Import\Resolvers\MediaMatchResolver;
use Capell\Backup\Services\Import\Resolvers\RelationMatchResolverRegistry;
use Capell\Backup\Support\AdminPageExporter;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;

class BackupServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'backup';

    public static string $packageName = 'capell-app/backup';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('backup')
            ->hasMigrations([
                'create_import_sessions_table',
                'drop_workspace_id_from_import_sessions_table',
                'create_backup_restores_table',
            ]);
    }

    public function packageRegistered(): void
    {
        CapellCore::registerPackage(
            static::$packageName,
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(static::$packageName),
            description: fn (): string => __('backup::package.description'),
        );

        if ($this->isPackageInstalled()) {
            $this->registerAdminPanelExtensions();
        }

        $this->app->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this->registerInstalledPackage();
        });
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(static::$packageName);
    }

    private function registerInstalledPackage(): void
    {
        CapellCore::registerModels([
            BackupRestore::class,
            ImportSession::class,
        ]);

        $this->app->singletonIf(BackupContextResolver::class, NullBackupContextResolver::class);
        $this->app->singletonIf(BackupRowContributor::class, NullBackupRowContributor::class);
        $this->app->singletonIf(PageCollisionDetector::class, NullPageCollisionDetector::class);

        $this->app->singleton(
            RelationMatchResolverRegistry::class,
            static function (): RelationMatchResolverRegistry {
                $registry = new RelationMatchResolverRegistry;
                $registry->register('layouts', new KeyedMatchResolver(Layout::class));
                $registry->register('layouts', new FingerprintMatchResolver(Layout::class));
                $registry->register('types', new KeyedMatchResolver(Type::class));
                $registry->register('types', new FingerprintMatchResolver(Type::class));
                $registry->register('sites', new KeyedMatchResolver(Site::class, keyColumn: 'slug'));
                $registry->register('media', new MediaMatchResolver);

                return $registry;
            },
        );

        if (interface_exists(PageExporter::class)) {
            $this->app->singleton(PageExporter::class, AdminPageExporter::class);
        }

        if (class_exists(SendImportSessionNotifications::class)) {
            Event::listen(ImportCompleted::class, [SendImportSessionNotifications::class, 'handleCompleted']);
            Event::listen(ImportFailed::class, [SendImportSessionNotifications::class, 'handleFailed']);
        }

        if (class_exists(ImportSessionPolicy::class)) {
            Gate::policy(ImportSession::class, ImportSessionPolicy::class);
        }

        $this->registerAdminPanelExtensions();
    }

    private function registerAdminPanelExtensions(): void
    {
        if (class_exists(CapellAdminManager::class) && class_exists(ImportSessionResource::class)) {
            $registerImportSessionResource = static function (CapellAdminManager $capellAdminManager): void {
                if (! $capellAdminManager->hasResource('ImportSession')) {
                    $capellAdminManager->registerResource('ImportSession', ImportSessionResource::class);
                }

                $capellAdminManager->registerPage(ImportSitesPage::class);
            };

            $this->app->afterResolving(CapellAdminManager::class, $registerImportSessionResource);

            if ($this->app->resolved(CapellAdminManager::class)) {
                $registerImportSessionResource($this->app->make(CapellAdminManager::class));
            }
        }
    }
}
