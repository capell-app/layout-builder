<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets;

use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\LayoutModelEnum;
use Capell\Layout\Enums\LayoutResourceEnum;
use Capell\Layout\Filament\Resources\Contents\Schemas\WidgetForm;
use Capell\Layout\Filament\Resources\Widgets\Pages\CreateWidget;
use Capell\Layout\Filament\Resources\Widgets\Pages\EditWidget;
use Capell\Layout\Filament\Resources\Widgets\Pages\ListWidgets;
use Capell\Layout\Filament\Resources\Widgets\RelationManagers\LayoutsRelationManager;
use Capell\Layout\Models\Widget;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WidgetResource extends Resource
{
    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function getResourceType(): string
    {
        return LayoutResourceEnum::Widget->name;
    }

    /**
     * @return class-string<Widget>
     */
    public static function getModel(): string
    {
        return CapellCore::getModel(LayoutModelEnum::Widget->name);
    }

    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-admin::navigation.widgets'));
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_layouts'));
    }

    public static function getNavigationBadge(): ?string
    {
        if (! config('capell-admin.resources.widget.navigation_badge')) {
            return null;
        }

        return number_format(static::getModel()::count());
    }

    public static function getPluralModelLabel(): string
    {
        return __('capell-admin::generic.widgets');
    }

    public static function getNavigationIcon(): string
    {
        return config('capell-admin.resources.widgets.navigation_icon', 'heroicon-o-gift');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'key', 'translations.title', 'meta->component', 'meta->file', 'meta->component_item'];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return WidgetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table;
    }

    public static function getRelations(): array
    {
        return [
            LayoutsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWidgets::route('/'),
            'edit' => EditWidget::route('/{record}/edit'),
            'create' => CreateWidget::route('/create'),
        ];
    }
}
