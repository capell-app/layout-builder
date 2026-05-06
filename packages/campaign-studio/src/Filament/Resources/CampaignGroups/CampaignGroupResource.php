<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Filament\Resources\CampaignGroups;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\CampaignStudio\Filament\Resources\CampaignGroups\Pages\CreateCampaignGroup;
use Capell\CampaignStudio\Filament\Resources\CampaignGroups\Pages\EditCampaignGroup;
use Capell\CampaignStudio\Filament\Resources\CampaignGroups\Pages\ListCampaignGroups;
use Capell\CampaignStudio\Filament\Resources\CampaignGroups\Schemas\CampaignGroupForm;
use Capell\CampaignStudio\Filament\Resources\CampaignGroups\Tables\CampaignGroupsTable;
use Capell\CampaignStudio\Models\CampaignGroup;
use Capell\CampaignStudio\Providers\CampaignStudioServiceProvider;
use Capell\Core\Facades\CapellCore;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

final class CampaignGroupResource extends Resource
{
    use HasConfiguredForm;
    use HasConfiguredTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Megaphone;

    protected static ?string $recordTitleAttribute = 'name';

    private static string $formConfigurator = CampaignGroupForm::class;

    private static string $tableConfigurator = CampaignGroupsTable::class;

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

    /** @return class-string<CampaignGroup> */
    #[Override]
    public static function getModel(): string
    {
        return CampaignGroup::class;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-campaign-studio::navigation.campaign-studio');
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-campaign-studio::navigation.campaign_groups');
    }

    public static function getPluralModelLabel(): string
    {
        return __('capell-campaign-studio::generic.campaign_groups');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(CampaignStudioServiceProvider::$packageName)->isInstalled();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaignGroups::route('/'),
            'create' => CreateCampaignGroup::route('/create'),
            'edit' => EditCampaignGroup::route('/{record}/edit'),
        ];
    }
}
