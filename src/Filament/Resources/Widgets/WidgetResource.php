<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Widgets;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\Core\Models\Widget;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\CreateWidget;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\EditWidget;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\ListWidgets;
use Capell\LayoutBuilder\Filament\Resources\Widgets\RelationManagers\LayoutsRelationManager;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Schemas\WidgetForm;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Tables\WidgetsTable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WidgetResource extends Resource
{
    use HasConfiguredForm;
    use HasConfiguredTable;
    use HasNavigationBadge;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $formConfigurator = WidgetForm::class;

    protected static string $tableConfigurator = WidgetsTable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Cube;

    protected static ?int $navigationSort = 5;

    public static function form(Schema $configurator): Schema
    {
        return static::getFormConfigurator()::configure($configurator);
    }

    public static function table(Table $table): Table
    {
        return static::getTableConfigurator()::configure($table);
    }

    public static function getResourceType(): ConfiguratorTypeEnum
    {
        return ConfiguratorTypeEnum::Widget;
    }

    /**
     * @return class-string<Widget>
     */
    public static function getModel(): string
    {
        return Widget::class;
    }

    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-admin::navigation.widgets'));
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_content'));
    }

    public static function getPluralModelLabel(): string
    {
        return __('capell-admin::navigation.widgets');
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('capell-layout-builder.resources.widget.icon', static::$navigationIcon);
    }

    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('capell-layout-builder.resources.widget.active_icon', static::$activeNavigationIcon);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'key', 'translations.title', 'meta->component', 'meta->file'];
    }

    /**
     * @param  Model&Widget  $record
     * @return array|string[]
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->title !== null && $record->title !== '') {
            $details[__('capell-admin::generic.title')] = $record->title;
        }

        return $details;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
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
