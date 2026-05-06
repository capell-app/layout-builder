<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Filament\Resources\CampaignCtaBlocks;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\CampaignStudio\Filament\Resources\CampaignCtaBlocks\Pages\CreateCampaignCtaBlock;
use Capell\CampaignStudio\Filament\Resources\CampaignCtaBlocks\Pages\EditCampaignCtaBlock;
use Capell\CampaignStudio\Filament\Resources\CampaignCtaBlocks\Pages\ListCampaignCtaBlocks;
use Capell\CampaignStudio\Filament\Resources\CampaignCtaBlocks\Schemas\CampaignCtaBlockForm;
use Capell\CampaignStudio\Filament\Resources\CampaignCtaBlocks\Tables\CampaignCtaBlocksTable;
use Capell\CampaignStudio\Models\CampaignCtaBlock;
use Capell\CampaignStudio\Providers\CampaignStudioServiceProvider;
use Capell\Core\Facades\CapellCore;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

final class CampaignCtaBlockResource extends Resource
{
    use HasConfiguredForm;
    use HasConfiguredTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCursorArrowRays;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::CursorArrowRays;

    protected static ?string $recordTitleAttribute = 'name';

    private static string $formConfigurator = CampaignCtaBlockForm::class;

    private static string $tableConfigurator = CampaignCtaBlocksTable::class;

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

    /** @return class-string<CampaignCtaBlock> */
    #[Override]
    public static function getModel(): string
    {
        return CampaignCtaBlock::class;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-campaign-studio::navigation.campaign-studio');
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-campaign-studio::navigation.cta_blocks');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(CampaignStudioServiceProvider::$packageName)->isInstalled();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaignCtaBlocks::route('/'),
            'create' => CreateCampaignCtaBlock::route('/create'),
            'edit' => EditCampaignCtaBlock::route('/{record}/edit'),
        ];
    }
}
