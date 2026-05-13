<?php

declare(strict_types=1);

namespace Capell\Tags\Filament\Resources\Tags;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\Admin\Support\SiteScope;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Tags\Filament\Resources\Tags\Pages\CreateTag;
use Capell\Tags\Filament\Resources\Tags\Pages\EditTag;
use Capell\Tags\Filament\Resources\Tags\Pages\ListTags;
use Capell\Tags\Filament\Resources\Tags\RelationManagers\PagesRelationManager;
use Capell\Tags\Filament\Resources\Tags\Schemas\TagForm;
use Capell\Tags\Filament\Resources\Tags\Tables\TagsTable;
use Capell\Tags\Models\Tag;
use Capell\Tags\Providers\TagsServiceProvider;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Override;
use RuntimeException;

class TagResource extends Resource
{
    use HasConfiguredForm;
    use HasConfiguredTable;
    use HasNavigationBadge;
    use Translatable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Tag;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $formConfigurator = TagForm::class;

    protected static string $tableConfigurator = TagsTable::class;

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

    /**
     * @return class-string<Tag>
     */
    #[Override]
    public static function getModel(): string
    {
        return Tag::class;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-admin::navigation.group_content');
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-tags::navigation.tags');
    }

    public static function getNavigationParentItem(): ?string
    {
        if (! CapellCore::isPackageInstalled('capell-app/blog')) {
            return null;
        }

        return __('capell-tags::generic.articles');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(TagsServiceProvider::$packageName)->isInstalled();
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return self::applySiteScope(parent::getEloquentQuery());
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return self::applySiteScope(parent::getGlobalSearchEloquentQuery())
            ->with(['site:id,name']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTags::route('/'),
            'create' => CreateTag::route('/create'),
            'edit' => EditTag::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('capell-tags::generic.tags');
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            PagesRelationManager::class,
        ];
    }

    public static function getTranslatableLocales(): array
    {
        $locales = Language::getLanguageLocales();

        if ($locales !== []) {
            return $locales;
        }

        throw new RuntimeException('At least one language must be defined to use translatable features.');
    }

    private static function applySiteScope(Builder $query): Builder
    {
        $actor = auth()->user();

        if (! $actor instanceof Authenticatable || SiteScope::isGlobalActor($actor) || ! method_exists($actor, 'getAssignedSiteIds')) {
            return $query;
        }

        $assignedSiteIds = $actor->getAssignedSiteIds();

        return $query->where(function (Builder $query) use ($assignedSiteIds): void {
            $query->whereNull('site_id');

            if ($assignedSiteIds->isNotEmpty()) {
                $query->orWhereIn('site_id', $assignedSiteIds);
            }
        });
    }
}
