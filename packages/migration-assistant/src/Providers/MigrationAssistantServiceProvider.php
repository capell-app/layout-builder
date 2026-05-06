<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Providers;

use Capell\Admin\Contracts\Backup\PageExporter;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Support\CapellAdminManager;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\MigrationAssistant\Contracts\MigrationAssistantContextResolver;
use Capell\MigrationAssistant\Contracts\MigrationAssistantRowContributor;
use Capell\MigrationAssistant\Contracts\NullMigrationAssistantContextResolver;
use Capell\MigrationAssistant\Contracts\NullMigrationAssistantRowContributor;
use Capell\MigrationAssistant\Contracts\NullPageCollisionDetector;
use Capell\MigrationAssistant\Contracts\PageCollisionDetector;
use Capell\MigrationAssistant\Events\ImportCompleted;
use Capell\MigrationAssistant\Events\ImportFailed;
use Capell\MigrationAssistant\Filament\Pages\ImportSitesPage;
use Capell\MigrationAssistant\Filament\Resources\ImportSessions\ImportSessionResource;
use Capell\MigrationAssistant\Listeners\SendImportSessionNotifications;
use Capell\MigrationAssistant\Models\ImportRollbackReport;
use Capell\MigrationAssistant\Models\ImportSession;
use Capell\MigrationAssistant\Policies\ImportSessionPolicy;
use Capell\MigrationAssistant\Services\Import\CsvReader;
use Capell\MigrationAssistant\Services\Import\Resolvers\FingerprintMatchResolver;
use Capell\MigrationAssistant\Services\Import\Resolvers\KeyedMatchResolver;
use Capell\MigrationAssistant\Services\Import\Resolvers\MediaMatchResolver;
use Capell\MigrationAssistant\Services\Import\Resolvers\RelationMatchResolverRegistry;
use Capell\MigrationAssistant\Services\Import\XmlReader;
use Capell\MigrationAssistant\Support\AdminPageExporter;
use Capell\MigrationAssistant\Support\ImportSourceRegistry;
use Capell\MigrationAssistant\Support\ImportTargetRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;

class MigrationAssistantServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'migration-assistant';

    public static string $packageName = 'capell-app/migration-assistant';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('migration-assistant')
            ->hasMigrations([
                'create_import_sessions_table',
                'create_import_rollback_dashboard-dashboard_reports_table',
            ]);
    }

    public function packageRegistered(): void
    {
        CapellCore::registerPackage(
            static::$packageName,
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(static::$packageName),
            description: fn (): string => __('migration-assistant::package.description'),
        );

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

        $this->app->singletonIf(MigrationAssistantContextResolver::class, NullMigrationAssistantContextResolver::class);
        $this->app->singletonIf(MigrationAssistantRowContributor::class, NullMigrationAssistantRowContributor::class);
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
