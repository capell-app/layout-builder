<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Widgets;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasFormConfigurator;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\Admin\Filament\Concerns\HasTableConfigurator;
use Capell\Core\Facades\CapellCore;
use Capell\Mosaic\Enums\ModelEnum;
use Capell\Mosaic\Filament\Resources\Widgets\Pages\CreateWidget;
use Capell\Mosaic\Filament\Resources\Widgets\Pages\EditWidget;
use Capell\Mosaic\Filament\Resources\Widgets\Pages\ListWidgets;
use Capell\Mosaic\Filament\Resources\Widgets\RelationManagers\LayoutsRelationManager;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\WidgetForm;
use Capell\Mosaic\Filament\Resources\Widgets\Tables\WidgetsTable;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Providers\MosaicServiceProvider;
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
    use HasFormConfigurator;
    use HasNavigationBadge;
    use HasTableConfigurator;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $formConfigurator = WidgetForm::class;

    protected static string $tableConfigurator = WidgetsTable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Cube;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return static::getFormConfigurator()::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return static::getTableConfigurator()::configure($table);
    }

    public static function getResourceType(): string
    {
        return 'Widgets';
    }

    /**
     * @return class-string<Widget>
     */
    public static function getModel(): string
    {
        return CapellCore::getModel(ModelEnum::Widget->name);
    }

    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-mosaic::navigation.widgets'));
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_design'));
    }

    public static function getPluralModelLabel(): string
    {
        return __('capell-mosaic::generic.widgets');
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('capell-mosaic.resources.widget.icon', static::$navigationIcon);
    }

    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('capell-mosaic.resources.widget.active_icon', static::$activeNavigationIcon);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(MosaicServiceProvider::$packageName)->isInstalled();
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

        if ($record->title) {
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
