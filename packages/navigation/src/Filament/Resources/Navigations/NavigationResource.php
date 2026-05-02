<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Resources\Navigations;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\Admin\Support\SiteScope;
use Capell\Navigation\Filament\Resources\Navigations\Pages\CreateNavigation;
use Capell\Navigation\Filament\Resources\Navigations\Pages\EditNavigation;
use Capell\Navigation\Filament\Resources\Navigations\Pages\ListNavigations;
use Capell\Navigation\Filament\Resources\Navigations\Schemas\NavigationForm;
use Capell\Navigation\Filament\Resources\Navigations\Tables\NavigationsTable;
use Capell\Navigation\Models\Navigation;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Override;

class NavigationResource extends Resource
{
    use HasConfiguredForm;
    use HasConfiguredTable;
    use HasNavigationBadge;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Bars3;

    protected static string $formConfigurator = NavigationForm::class;

    protected static string $tableConfigurator = NavigationsTable::class;

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

    public static function getPluralModelLabel(): string
    {
        return __('capell-admin::generic.navigations');
    }

    public static function getModelLabel(): string
    {
        return __('capell-admin::generic.navigation');
    }

    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-admin::navigation.navigations'));
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_website'));
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('capell-admin.resources.navigation.icon', static::$navigationIcon);
    }

    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('capell-admin.resources.navigation.active_icon', static::$activeNavigationIcon);
    }

    /**
     * @return class-string<Navigation>
     */
    #[Override]
    public static function getModel(): string
    {
        return Navigation::class;
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return self::applySiteScope(parent::getEloquentQuery())
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'key'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        /** @var Navigation $record */
        return $record->name;
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return self::applySiteScope(parent::getGlobalSearchEloquentQuery())
            ->with([
                'site' => fn (BuilderContract $query): BuilderContract => $query->select(['id', 'name']),
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * @param  Navigation  $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->site !== null) {
            $details[__('capell-admin::generic.site')] = $record->site->name;
        }

        return $details;
    }

    public static function getPages(): array
    {
        $pages = parent::getPages();

        $pages['index'] = ListNavigations::route('/');
        $pages['create'] = CreateNavigation::route('/create');
        $pages['edit'] = EditNavigation::route('/{record}/edit');

        return $pages;
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
