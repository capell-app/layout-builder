<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Providers;

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\CampaignStudio\Console\Commands\InstallCampaignLayoutsCommand;
use Capell\CampaignStudio\Enums\CampaignWidgetComponentEnum;
use Capell\CampaignStudio\Filament\Extenders\Page\CampaignPageSchemaExtender;
use Capell\CampaignStudio\Listeners\RecordFormSubmissionConversion;
use Capell\CampaignStudio\Listeners\SyncCampaignLandingPageFromPage;
use Capell\CampaignStudio\Models\CampaignConversion;
use Capell\CampaignStudio\Models\CampaignConversionGoal;
use Capell\CampaignStudio\Models\CampaignCtaBlock;
use Capell\CampaignStudio\Models\CampaignGroup;
use Capell\CampaignStudio\Models\CampaignLandingPage;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Events\PageSaved;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;

final class CampaignStudioServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-campaign-studio';

    public static string $packageName = 'capell-app/campaign-studio';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-campaign-studio')
            ->hasTranslations()
            ->hasViews(self::$name)
            ->hasCommand(InstallCampaignLayoutsCommand::class)
            ->hasMigrations([
                'create_campaign_groups_table',
                'create_campaign_landing_pages_table',
                'create_campaign_cta_blocks_table',
                'create_campaign_conversion_goals_table',
                'create_campaign_conversions_table',
            ]);
    }

    public function registeringPackage(): void
    {
        $this
            ->registerPackageMetadata();

        $this->app->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this->bootInstalledPackage();
        });
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(self::$packageName);
    }

    private function bootInstalledPackage(): self
    {
        return $this
            ->registerModels()
            ->registerComponents()
            ->registerSchemaExtenders()
            ->registerPackageAssets()
            ->registerProtectedTables()
            ->registerListeners();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            description: fn (): string => __('capell-campaign-studio::package.description'),
        );

        return $this;
    }

    private function registerPackageAssets(): self
    {
        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', self::$packageName),
        );

        return $this;
    }

    private function registerModels(): self
    {
        CapellCore::registerModels([
            CampaignGroup::class,
            CampaignLandingPage::class,
            CampaignCtaBlock::class,
            CampaignConversionGoal::class,
            CampaignConversion::class,
        ]);

        return $this;
    }

    private function registerComponents(): self
    {
        CapellCore::registerComponents('Widget', CampaignWidgetComponentEnum::cases());

        return $this;
    }

    private function registerSchemaExtenders(): self
    {
        $this->app->singleton(CampaignPageSchemaExtender::class);
        $this->app->tag(CampaignPageSchemaExtender::class, PageSchemaExtender::TAG);

        return $this;
    }

    private function registerProtectedTables(): self
    {
        $tableNames = config('capell-campaign-studio.tables', []);

        if (! is_array($tableNames)) {
            return $this;
        }

        foreach ($tableNames as $tableName) {
            if (! is_string($tableName)) {
                continue;
            }

            if ($tableName === '') {
                continue;
            }

            CapellCore::registerProtectedTable(fn (): string => $tableName);
        }

        return $this;
    }

    private function registerListeners(): self
    {
        Event::listen(PageSaved::class, SyncCampaignLandingPageFromPage::class);

        $formSubmittedEvent = implode('\\', ['Capell', 'FormBuilder', 'Events', 'FormSubmitted']);

        if (class_exists($formSubmittedEvent)) {
            Event::listen($formSubmittedEvent, RecordFormSubmissionConversion::class);
        }

        return $this;
    }

    private function getVersion(): string
    {
        if (! class_exists(InstalledVersions::class) || ! InstalledVersions::isInstalled(self::$packageName)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion(self::$packageName) ?? 'dev';
    }
}
