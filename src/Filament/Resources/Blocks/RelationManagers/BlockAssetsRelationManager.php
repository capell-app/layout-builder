<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Blocks\RelationManagers;

use Capell\Admin\Filament\Concerns\HasRelationManagerBadge;
use Capell\LayoutBuilder\Filament\Resources\Blocks\Schemas\BlockAssetForm;
use Capell\LayoutBuilder\Filament\Resources\Blocks\Tables\BlockAssetsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Override;

class BlockAssetsRelationManager extends RelationManager
{
    use HasRelationManagerBadge;

    protected static string $relationship = 'blockAssets';

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-admin::tab.assets');
    }

    #[Override]
    public function form(Schema $configurator): Schema
    {
        return BlockAssetForm::configure($configurator);
    }

    public function table(Table $table): Table
    {
        return BlockAssetsTable::configure($table);
    }
}
