<?php

declare(strict_types=1);

namespace Capell\Plugins\Filament\Resources;

use Capell\Plugins\Filament\Resources\MarketplacePlugin\Pages\CreateMarketplacePlugin;
use Capell\Plugins\Filament\Resources\MarketplacePlugin\Pages\EditMarketplacePlugin;
use Capell\Plugins\Filament\Resources\MarketplacePlugin\Pages\ListMarketplacePlugins;
use Capell\Plugins\Filament\Resources\MarketplacePlugin\Schemas\MarketplacePluginForm;
use Capell\Plugins\Filament\Resources\MarketplacePlugin\Tables\MarketplacePluginsTable;
use Capell\Plugins\Models\MarketplacePlugin;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

class MarketplacePluginResource extends Resource
{
    protected static ?string $model = MarketplacePlugin::class;

    protected static ?string $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $activeNavigationIcon = Heroicon::ShoppingCart;

    protected static ?string $navigationLabel = 'Marketplace Plugins';

    protected static ?string $navigationGroup = 'Plugins';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'title';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return MarketplacePluginForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return MarketplacePluginsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMarketplacePlugins::route('/'),
            'create' => CreateMarketplacePlugin::route('/create'),
            'edit' => EditMarketplacePlugin::route('/{record}'),
        ];
    }
}
