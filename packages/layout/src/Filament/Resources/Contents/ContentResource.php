<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Contents;

use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\LayoutModelEnum;
use Capell\Layout\Enums\LayoutResourceEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Filament\Resources\Contents\Pages\CreateContent;
use Capell\Layout\Filament\Resources\Contents\Pages\EditContent;
use Capell\Layout\Filament\Resources\Contents\Pages\ListContents;
use Capell\Layout\Filament\Resources\Contents\RelationManagers\ContentAssetsRelationManager;
use Capell\Layout\Filament\Resources\Contents\RelationManagers\PagesRelationManager;
use Capell\Layout\Filament\Resources\Contents\RelationManagers\WidgetsRelationManager;
use Capell\Layout\Filament\Resources\Widgets\Schemas\WidgetForm;
use Capell\Layout\Filament\Resources\Widgets\Tables\WidgetsTable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContentResource extends Resource
{
    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function getResourceType(): string
    {
        return LayoutResourceEnum::Content->name;
    }

    public static function form(Schema $schema): Schema
    {
        return WidgetForm::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withDrafts()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'translations.title'];
    }

    public static function getModel(): string
    {
        return CapellCore::getModel(LayoutModelEnum::Content->name);
    }

    public static function getNavigationBadge(): ?string
    {
        if (! config('capell-admin.resources.content.navigation_badge')) {
            return null;
        }

        return number_format(static::getModel()::count());
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_resources'));
    }

    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-admin::navigation.contents'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContents::route('/'),
            'create' => CreateContent::route('/create'),
            'edit' => EditContent::route('/{record}/edit'),
        ];
    }

    public static function getNavigationIcon(): ?string
    {
        return CapellCore::getAsset(LayoutTypeEnum::Content->name)->getIcon();
    }

    public static function getPluralModelLabel(): string
    {
        return __('capell-admin::generic.contents');
    }

    public static function getRelations(): array
    {
        return [
            ContentAssetsRelationManager::class,
            WidgetsRelationManager::class,
            PagesRelationManager::class,
        ];
    }

    public static function table(Table $table): Table
    {
        return WidgetsTable::configure($table);
    }

    public static function getSiteId(HasTable $livewire)
    {
        return match (true) {
            $livewire instanceof ListContents => $livewire->activeTab,
            default => $livewire->getTableFilterState('filter')['site_id'] ?? null,
        };
    }
}
