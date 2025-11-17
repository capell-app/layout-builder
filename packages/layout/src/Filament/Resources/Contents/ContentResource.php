<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Contents;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasFormConfigurator;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\Admin\Filament\Concerns\HasTableConfigurator;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\ResourceEnum;
use Capell\Layout\Filament\Resources\Contents\Pages\CreateContent;
use Capell\Layout\Filament\Resources\Contents\Pages\EditContent;
use Capell\Layout\Filament\Resources\Contents\Pages\ListContents;
use Capell\Layout\Filament\Resources\Contents\RelationManagers\ContentAssetsRelationManager;
use Capell\Layout\Filament\Resources\Contents\RelationManagers\PagesRelationManager;
use Capell\Layout\Filament\Resources\Contents\RelationManagers\WidgetsRelationManager;
use Capell\Layout\Filament\Resources\Contents\Schemas\ContentForm;
use Capell\Layout\Filament\Resources\Contents\Tables\ContentsTable;
use Capell\Layout\Filament\Resources\Contents\Widgets\ContentAlertsWidget;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContentResource extends Resource
{
    use HasFormConfigurator;
    use HasNavigationBadge;
    use HasTableConfigurator;

    protected static ?string $recordTitleAttribute = 'name';


    /** @var class-string<FormConfigurator> */
    protected static string $formConfigurator = ContentForm::class;

    /** @var class-string<TableConfigurator> */
    protected static string $tableConfigurator = ContentsTable::class;

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
        return ResourceEnum::Content->name;
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
        return CapellCore::getModel(ModelEnum::Content->name);
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_assets'));
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

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
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

    public static function getWidgets(): array
    {
        return [
            ContentAlertsWidget::class,
        ];
    }
}
