<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\ThemeStudio\Admin\Contracts\ThemeDraftPublisher;
use Capell\ThemeStudio\Admin\Filament\Pages\ThemeStudioPage;
use Capell\ThemeStudio\Admin\Listeners\ActivateApprovedThemeDraft;
use Capell\ThemeStudio\Admin\Publishing\StandaloneThemeDraftPublisher;
use Capell\ThemeStudio\Admin\Publishing\WorkspaceThemeDraftPublisher;
use Capell\ThemeStudio\Admin\Schemas\ThemeStudioSettingsSchema;
use Capell\ThemeStudio\Core\Settings\ThemeStudioSettings;
use Capell\Workspaces\Events\WorkspaceStateChanged;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;

final class ThemeStudioAdminServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-theme-studio-admin';

    public static string $packageName = 'capell-app/theme-studio-admin';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasTranslations()
            ->hasViews();
    }

    public function packageRegistered(): void
    {
        $this->registerPackageMetadata();

        $this->app->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this
                ->registerSettings()
                ->registerPublishing()
                ->registerWorkspaceApprovalHandoff()
                ->registerPages();
        });
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(self::$packageName);
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            setting: ThemeStudioSettings::class,
            description: fn (): string => __('capell-theme-studio-admin::package.description'),
        );

        return $this;
    }

    private function registerSettings(): self
    {
        $registerSettingsSchema = function (SettingsSchemaRegistry $registry): void {
            $registry->register(ThemeStudioSettings::group(), ThemeStudioSettingsSchema::class);
        };

        $this->app->afterResolving(SettingsSchemaRegistry::class, $registerSettingsSchema);

        if ($this->app->resolved(SettingsSchemaRegistry::class)) {
            $registerSettingsSchema($this->app->make(SettingsSchemaRegistry::class));
        }

        return $this;
    }

    private function registerPublishing(): self
    {
        $this->app->bindIf(
            ThemeDraftPublisher::class,
            fn (): ThemeDraftPublisher => CapellCore::isPackageInstalled('capell-app/workspaces')
                ? resolve(WorkspaceThemeDraftPublisher::class)
                : resolve(StandaloneThemeDraftPublisher::class),
        );

        return $this;
    }

    private function registerWorkspaceApprovalHandoff(): self
    {
        if (class_exists(WorkspaceStateChanged::class)) {
            Event::listen(
                WorkspaceStateChanged::class,
                ActivateApprovedThemeDraft::class,
            );
        }

        return $this;
    }

    private function registerPages(): self
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(ThemeStudioPage::class));

        return $this;
    }
}
