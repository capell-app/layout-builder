<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Collections;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasFormConfigurator;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\Admin\Filament\Concerns\HasTableConfigurator;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Filament\Resources\Collections\Pages\CreateCollection;
use Capell\Layout\Filament\Resources\Collections\Pages\EditCollection;
use Capell\Layout\Filament\Resources\Collections\Pages\ListCollections;
use Capell\Layout\Filament\Resources\Collections\RelationManagers\CollectionAssetsRelationManager;
use Capell\Layout\Filament\Resources\Collections\RelationManagers\PagesRelationManager;
use Capell\Layout\Filament\Resources\Collections\RelationManagers\WidgetsRelationManager;
use Capell\Layout\Filament\Resources\Collections\Schemas\CollectionForm;
use Capell\Layout\Filament\Resources\Collections\Tables\CollectionsTable;
use Capell\Layout\Filament\Resources\Collections\Widgets\ContentAlertsWidget;
use Capell\Layout\Models\Content;
use Capell\Layout\Providers\LayoutServiceProvider;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class CollectionResource extends Resource
{
    use HasFormConfigurator;
    use HasNavigationBadge;
    use HasTableConfigurator;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $formConfigurator = CollectionForm::class;

    protected static string $tableConfigurator = CollectionsTable::class;

    public static function form(Schema $schema): Schema
    {
        return static::getFormConfigurator()::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return static::getTableConfigurator()::configure($table);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(LayoutServiceProvider::$packageName)->isInstalled();
    }

    public static function getResourceType(): string
    {
        return 'Collections';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'translations.title'];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with([
                'site:id,name,default',
                'type:id,name',
                'ancestors',
            ]);
    }

    /**
     * @param  Content  $record
     * @return array|string[]
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->title !== $record->name) {
            $details[] = $record->title;
        }

        if (($breadcrumb = self::buildGlobalSearchBreadcrumbs($record)) instanceof HtmlString) {
            $details[] = $breadcrumb;
        }

        return $details;
    }

    /**
     * @return class-string<Content>
     */
    public static function getModel(): string
    {
        return CapellCore::getModel(ModelEnum::Content->name);
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_library'));
    }

    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-layout::navigation.contents'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollections::route('/'),
            'create' => CreateCollection::route('/create'),
            'edit' => EditCollection::route('/{record}/edit'),
        ];
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return CapellCore::getAsset(LayoutTypeEnum::Content->name)->getIcon();
    }

    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return CapellCore::getAsset(LayoutTypeEnum::Content->name)->getActiveIcon();
    }

    public static function getPluralModelLabel(): string
    {
        return __('capell-admin::generic.contents');
    }

    public static function getRelations(): array
    {
        return [
            CollectionAssetsRelationManager::class,
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

    private static function buildGlobalSearchBreadcrumbs(Content $record): ?HtmlString
    {
        $breadcrumbs = [];

        if ($record->site !== null && ! $record->site->default) {
            $breadcrumbs[] = $record->site->name;
        }

        if ($record->ancestors->isNotEmpty()) {
            $breadcrumbs[] = $record->ancestors->pluck('name')->implode(' &raquo; ');
        }

        if (filled($breadcrumbs)) {
            return new HtmlString(implode(' &raquo; ', $breadcrumbs));
        }

        return null;
    }
}
