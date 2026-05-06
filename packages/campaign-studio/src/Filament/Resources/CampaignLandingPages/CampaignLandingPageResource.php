<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Filament\Resources\CampaignLandingPages;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\CampaignStudio\Filament\Resources\CampaignLandingPages\Pages\CreateCampaignLandingPage;
use Capell\CampaignStudio\Filament\Resources\CampaignLandingPages\Pages\EditCampaignLandingPage;
use Capell\CampaignStudio\Filament\Resources\CampaignLandingPages\Pages\ListCampaignLandingPages;
use Capell\CampaignStudio\Filament\Resources\CampaignLandingPages\Schemas\CampaignLandingPageForm;
use Capell\CampaignStudio\Filament\Resources\CampaignLandingPages\Tables\CampaignLandingPagesTable;
use Capell\CampaignStudio\Models\CampaignLandingPage;
use Capell\CampaignStudio\Providers\CampaignStudioServiceProvider;
use Capell\Core\Facades\CapellCore;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

final class CampaignLandingPageResource extends Resource
{
    use HasConfiguredForm;
    use HasConfiguredTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::DocumentText;

    protected static ?string $recordTitleAttribute = 'headline';

    private static string $formConfigurator = CampaignLandingPageForm::class;

    private static string $tableConfigurator = CampaignLandingPagesTable::class;

    #[Override]
    public static function form(Schema $configurator): Schema
    {
        return self::getFormConfigurator()::configure($configurator);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return self::getTableConfigurator()::configure($table);
    }

    /** @return class-string<CampaignLandingPage> */
    #[Override]
    public static function getModel(): string
    {
        return CampaignLandingPage::class;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-campaign-studio::navigation.campaign-studio');
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-campaign-studio::navigation.landing_pages');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(CampaignStudioServiceProvider::$packageName)->isInstalled();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaignLandingPages::route('/'),
            'create' => CreateCampaignLandingPage::route('/create'),
            'edit' => EditCampaignLandingPage::route('/{record}/edit'),
        ];
    }
}
