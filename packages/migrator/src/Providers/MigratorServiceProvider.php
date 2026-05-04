<?php

declare(strict_types=1);

namespace Capell\Migrator\Providers;

use Capell\Admin\Contracts\Migrator\PageExporter;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Support\CapellAdminManager;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Migrator\Contracts\MigratorContextResolver;
use Capell\Migrator\Contracts\MigratorRowContributor;
use Capell\Migrator\Contracts\NullMigratorContextResolver;
use Capell\Migrator\Contracts\NullMigratorRowContributor;
use Capell\Migrator\Contracts\NullPageCollisionDetector;
use Capell\Migrator\Contracts\PageCollisionDetector;
use Capell\Migrator\Events\ImportCompleted;
use Capell\Migrator\Events\ImportFailed;
use Capell\Migrator\Filament\Pages\ImportSitesPage;
use Capell\Migrator\Filament\Resources\ImportSessions\ImportSessionResource;
use Capell\Migrator\Listeners\SendImportSessionNotifications;
use Capell\Migrator\Models\ImportRollbackReport;
use Capell\Migrator\Models\ImportSession;
use Capell\Migrator\Policies\ImportSessionPolicy;
use Capell\Migrator\Services\Import\CsvReader;
use Capell\Migrator\Services\Import\Resolvers\FingerprintMatchResolver;
use Capell\Migrator\Services\Import\Resolvers\KeyedMatchResolver;
use Capell\Migrator\Services\Import\Resolvers\MediaMatchResolver;
use Capell\Migrator\Services\Import\Resolvers\RelationMatchResolverRegistry;
use Capell\Migrator\Services\Import\XmlReader;
use Capell\Migrator\Support\AdminPageExporter;
use Capell\Migrator\Support\ImportSourceRegistry;
use Capell\Migrator\Support\ImportTargetRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;

class MigratorServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'migrator';

    public static string $packageName = 'capell-app/migrator';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('migrator')
            ->hasMigrations([
                'create_import_sessions_table',
                'create_import_rollback_reports_table',
            ]);
    }

    public function packageRegistered(): void
    {
        CapellCore::registerPackage(
            static::$packageName,
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(static::$packageName),
            description: fn (): string => __('migrator::package.description'),
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
            ImportRollbackReport::class,
            ImportSession::class,
        ]);

        $this->app->singletonIf(MigratorContextResolver::class, NullMigratorContextResolver::class);
        $this->app->singletonIf(MigratorRowContributor::class, NullMigratorRowContributor::class);
        $this->app->singletonIf(PageCollisionDetector::class, NullPageCollisionDetector::class);

        $this->app->singleton(ImportTargetRegistry::class);
        $this->app->singleton(ImportSourceRegistry::class, static function (): ImportSourceRegistry {
            $registry = new ImportSourceRegistry;
            $registry->register(new CsvReader);
            $registry->register(new XmlReader);

            return $registry;
        });

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
                $capellAdminManager->contributeToAdminSurface(
                    AdminSurfaceContributionData::resource(ImportSessionResource::class, group: 'ImportSession'),
                );
                $capellAdminManager->contributeToAdminSurface(AdminSurfaceContributionData::page(ImportSitesPage::class));
            };

            $this->app->afterResolving(CapellAdminManager::class, $registerImportSessionResource);

            if ($this->app->resolved(CapellAdminManager::class)) {
                $registerImportSessionResource($this->app->make(CapellAdminManager::class));
            }
        }
    }
}
