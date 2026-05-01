<?php

declare(strict_types=1);

namespace Capell\Campaigns\Providers;

use Capell\Campaigns\Listeners\RecordFormSubmissionConversion;
use Capell\Campaigns\Models\CampaignConversion;
use Capell\Campaigns\Models\CampaignConversionGoal;
use Capell\Campaigns\Models\CampaignCtaBlock;
use Capell\Campaigns\Models\CampaignGroup;
use Capell\Campaigns\Models\CampaignLandingPage;
use Capell\Core\Data\VendorAssetData;
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
        if (class_exists('Capell\\Forms\\Events\\FormSubmitted')) {
            Event::listen('Capell\\Forms\\Events\\FormSubmitted', RecordFormSubmissionConversion::class);
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
