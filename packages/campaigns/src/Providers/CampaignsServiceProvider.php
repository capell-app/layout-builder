<?php

declare(strict_types=1);

namespace Capell\Campaigns\Providers;

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Campaigns\Console\Commands\InstallCampaignLayoutsCommand;
use Capell\Campaigns\Enums\CampaignWidgetComponentEnum;
use Capell\Campaigns\Filament\Extenders\Page\CampaignPageSchemaExtender;
use Capell\Campaigns\Listeners\RecordFormSubmissionConversion;
use Capell\Campaigns\Listeners\SyncCampaignLandingPageFromPage;
use Capell\Campaigns\Models\CampaignConversion;
use Capell\Campaigns\Models\CampaignConversionGoal;
use Capell\Campaigns\Models\CampaignCtaBlock;
use Capell\Campaigns\Models\CampaignGroup;
use Capell\Campaigns\Models\CampaignLandingPage;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Events\PageSaved;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;

final class CampaignsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-campaigns';

    public static string $packageName = 'capell-app/campaigns';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-campaigns')
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
            ->registerPackageMetadata()
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
            description: fn (): string => __('capell-campaigns::package.description'),
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
        $tableNames = config('capell-campaigns.tables', []);

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

        $formSubmittedEvent = implode('\\', ['Capell', 'Forms', 'Events', 'FormSubmitted']);

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
