<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Blocks;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Resources\Blocks\Pages\CreateBlock;
use Capell\LayoutBuilder\Filament\Resources\Blocks\Pages\EditBlock;
use Capell\LayoutBuilder\Filament\Resources\Blocks\Pages\ListBlocks;
use Capell\LayoutBuilder\Filament\Resources\Blocks\RelationManagers\LayoutsRelationManager;
use Capell\LayoutBuilder\Filament\Resources\Blocks\Schemas\BlockForm;
use Capell\LayoutBuilder\Filament\Resources\Blocks\Tables\BlocksTable;
use Capell\LayoutBuilder\Models\Block;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Override;

class BlockResource extends Resource
{
    use HasConfiguredForm;
    use HasConfiguredTable;
    use HasNavigationBadge;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $formConfigurator = BlockForm::class;

    protected static string $tableConfigurator = BlocksTable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Cube;

    protected static ?int $navigationSort = 5;

    #[Override]
    public static function form(Schema $configurator): Schema
    {
        return static::getFormConfigurator()::configure($configurator);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return static::getTableConfigurator()::configure($table);
    }

    public static function getResourceType(): ConfiguratorTypeEnum
    {
        return ConfiguratorTypeEnum::Block;
    }

    /**
     * @return class-string<Block>
     */
    #[Override]
    public static function getModel(): string
    {
        return Block::class;
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-layout-builder::navigation.blocks'));
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_layouts'));
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('capell-layout-builder::navigation.blocks');
    }

    #[Override]
    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('capell-layout-builder.resources.block.icon', static::$navigationIcon);
    }

    #[Override]
    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('capell-layout-builder.resources.block.active_icon', static::$activeNavigationIcon);
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    #[Override]
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'key', 'translations.title', 'component', 'view_file'];
    }

    /**
     * @param  Model&Block  $record
     * @return array|string[]
     */
    #[Override]
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->title !== null && $record->title !== '') {
            $details[__('capell-admin::generic.title')] = $record->title;
        }

        return $details;
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            LayoutsRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListBlocks::route('/'),
            'edit' => EditBlock::route('/{record}/edit'),
            'create' => CreateBlock::route('/create'),
        ];
    }
}
