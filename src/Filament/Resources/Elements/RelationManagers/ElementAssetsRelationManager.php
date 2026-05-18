<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Elements\RelationManagers;

use Capell\Admin\Filament\Concerns\HasRelationManagerBadge;
use Capell\LayoutBuilder\Filament\Resources\Elements\Schemas\ElementAssetForm;
use Capell\LayoutBuilder\Filament\Resources\Elements\Tables\ElementAssetsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Override;

class ElementAssetsRelationManager extends RelationManager
{
    use HasRelationManagerBadge;

    protected static string $relationship = 'elementAssets';

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-admin::tab.assets');
    }

    #[Override]
    public function form(Schema $configurator): Schema
    {
        return ElementAssetForm::configure($configurator);
    }

    public function table(Table $table): Table
    {
        return ElementAssetsTable::configure($table);
    }
}
