<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Resources\CampaignConversionGoals;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Campaigns\Filament\Resources\CampaignConversionGoals\Pages\CreateCampaignConversionGoal;
use Capell\Campaigns\Filament\Resources\CampaignConversionGoals\Pages\EditCampaignConversionGoal;
use Capell\Campaigns\Filament\Resources\CampaignConversionGoals\Pages\ListCampaignConversionGoals;
use Capell\Campaigns\Filament\Resources\CampaignConversionGoals\Schemas\CampaignConversionGoalForm;
use Capell\Campaigns\Filament\Resources\CampaignConversionGoals\Tables\CampaignConversionGoalsTable;
use Capell\Campaigns\Models\CampaignConversionGoal;
use Capell\Campaigns\Providers\CampaignsServiceProvider;
use Capell\Core\Facades\CapellCore;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

final class CampaignConversionGoalResource extends Resource
{
    use HasConfiguredForm;
    use HasConfiguredTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::ChartBar;

    protected static ?string $recordTitleAttribute = 'name';

    private static string $formConfigurator = CampaignConversionGoalForm::class;

    private static string $tableConfigurator = CampaignConversionGoalsTable::class;

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

    /** @return class-string<CampaignConversionGoal> */
    #[Override]
    public static function getModel(): string
    {
        return CampaignConversionGoal::class;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-campaigns::navigation.campaigns');
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-campaigns::navigation.conversion_goals');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(CampaignsServiceProvider::$packageName)->isInstalled();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaignConversionGoals::route('/'),
            'create' => CreateCampaignConversionGoal::route('/create'),
            'edit' => EditCampaignConversionGoal::route('/{record}/edit'),
        ];
    }
}
