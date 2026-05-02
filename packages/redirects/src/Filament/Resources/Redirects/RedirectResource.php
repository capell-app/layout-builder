<?php

declare(strict_types=1);

namespace Capell\Redirects\Filament\Resources\Redirects;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Admin\Support\SiteScope;
use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Models\PageUrl;
use Capell\Redirects\Filament\Resources\Redirects\Pages\ManageRedirects;
use Capell\Redirects\Filament\Resources\Redirects\Schemas\RedirectForm;
use Capell\Redirects\Filament\Resources\Redirects\Tables\RedirectsTable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Override;

class RedirectResource extends Resource
{
    use HasConfiguredForm;
    use HasConfiguredTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnRight;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::ArrowUturnRight;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $formConfigurator = RedirectForm::class;

    protected static string $tableConfigurator = RedirectsTable::class;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return static::getFormConfigurator()::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return static::getTableConfigurator()::configure($table);
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('type', UrlTypeEnum::Redirect)
            ->with([
                'language',
                'site',
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        return SiteScope::applyForCurrentActor($query);
    }

    /**
     * @return class-string<PageUrl>
     */
    #[Override]
    public static function getModel(): string
    {
        return PageUrl::class;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('redirects::navigation.group_website');
    }

    public static function getNavigationLabel(): string
    {
        return __('redirects::navigation.redirects');
    }

    public static function getPluralModelLabel(): string
    {
        return __('redirects::navigation.redirects');
    }

    public static function getModelLabel(): string
    {
        return __('redirects::generic.redirect');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRedirects::route('/'),
        ];
    }
}
