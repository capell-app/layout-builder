<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Sections;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasFormConfigurator;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\Admin\Filament\Concerns\HasTableConfigurator;
use Capell\Core\Facades\CapellCore;
use Capell\Mosaic\Enums\LayoutTypeEnum;
use Capell\Mosaic\Enums\ModelEnum;
use Capell\Mosaic\Filament\Resources\Sections\Pages\CreateSection;
use Capell\Mosaic\Filament\Resources\Sections\Pages\EditSection;
use Capell\Mosaic\Filament\Resources\Sections\Pages\ListSections;
use Capell\Mosaic\Filament\Resources\Sections\RelationManagers\PagesRelationManager;
use Capell\Mosaic\Filament\Resources\Sections\RelationManagers\SectionAssetsRelationManager;
use Capell\Mosaic\Filament\Resources\Sections\RelationManagers\WidgetsRelationManager;
use Capell\Mosaic\Filament\Resources\Sections\Schemas\SectionForm;
use Capell\Mosaic\Filament\Resources\Sections\Tables\SectionsTable;
use Capell\Mosaic\Filament\Resources\Sections\Widgets\ContentAlertsWidget;
use Capell\Mosaic\Models\Section;
use Capell\Mosaic\Providers\MosaicServiceProvider;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class SectionResource extends Resource
{
    use HasFormConfigurator;
    use HasNavigationBadge;
    use HasTableConfigurator;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $formConfigurator = SectionForm::class;

    protected static string $tableConfigurator = SectionsTable::class;

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
        return CapellCore::getPackage(MosaicServiceProvider::$packageName)->isInstalled();
    }

    public static function getResourceType(): string
    {
        return 'Contents';
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
     * @param  Section  $record
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
     * @return class-string<Section>
     */
    public static function getModel(): string
    {
        return CapellCore::getModel(ModelEnum::Section->name);
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_library'));
    }

    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-mosaic::navigation.contents'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSections::route('/'),
            'create' => CreateSection::route('/create'),
            'edit' => EditSection::route('/{record}/edit'),
        ];
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return CapellCore::getAsset(LayoutTypeEnum::Section->name)->getIcon();
    }

    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return CapellCore::getAsset(LayoutTypeEnum::Section->name)->getActiveIcon();
    }

    public static function getPluralModelLabel(): string
    {
        return __('capell-admin::generic.contents');
    }

    public static function getRelations(): array
    {
        return [
            SectionAssetsRelationManager::class,
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

    private static function buildGlobalSearchBreadcrumbs(Section $record): ?HtmlString
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
