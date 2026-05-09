<?php

declare(strict_types=1);

namespace Capell\HtmlCache\Filament\Resources\CachedModelUrls;

use BackedEnum;
use Capell\Admin\Support\SiteScope;
use Capell\HtmlCache\Enums\HtmlCachePermission;
use Capell\HtmlCache\Filament\Resources\CachedModelUrls\Pages\ListCachedModelUrls;
use Capell\HtmlCache\Filament\Resources\CachedModelUrls\Tables\CachedModelUrlsTable;
use Capell\HtmlCache\Models\CachedModelUrl;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Override;

final class CachedModelUrlResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::DocumentMagnifyingGlass;

    protected static ?string $recordTitleAttribute = 'url';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return CachedModelUrlsTable::configure($table);
    }

    #[Override]
    public static function getModel(): string
    {
        return CachedModelUrl::class;
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return SiteScope::applyForCurrentActor(parent::getEloquentQuery(), denyWhenMissingActor: true);
    }

    #[Override]
    public static function canAccess(): bool
    {
        return self::canViewCacheMap();
    }

    #[Override]
    public static function canViewAny(): bool
    {
        return self::canViewCacheMap();
    }

    public static function getNavigationLabel(): string
    {
        return (string) __('capell-html-cache::admin.cached_model_urls');
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-html-cache::admin.navigation_group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCachedModelUrls::route('/'),
        ];
    }

    private static function canViewCacheMap(): bool
    {
        $actor = auth()->user();

        if (! $actor instanceof Authenticatable) {
            return false;
        }

        if (SiteScope::isGlobalActor($actor)) {
            return true;
        }

        if (method_exists($actor, 'hasPermissionTo')) {
            return $actor->hasPermissionTo(HtmlCachePermission::ViewCachedModelUrls->value) === true;
        }

        return method_exists($actor, 'can')
            && $actor->can(HtmlCachePermission::ViewCachedModelUrls->value) === true;
    }
}
