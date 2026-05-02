<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Widgets\RelationManagers;

use Capell\Admin\Filament\Concerns\HasRelationManagerBadge;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\WidgetAssetForm;
use Capell\Mosaic\Filament\Resources\Widgets\Tables\WidgetAssetsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WidgetAssetsRelationManager extends RelationManager
{
    use HasRelationManagerBadge;

    protected static string $relationship = 'widgetAssets';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-admin::tab.assets');
    }

    public function form(Schema $configurator): Schema
    {
        return WidgetAssetForm::configure($configurator);
    }

    public function table(Table $table): Table
    {
        return WidgetAssetsTable::configure($table);
    }
}
