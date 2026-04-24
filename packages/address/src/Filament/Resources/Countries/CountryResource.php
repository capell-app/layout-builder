<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Resources\Countries;

use BackedEnum;
use Capell\Address\Filament\Resources\Countries\Pages\ManageCountries;
use Capell\Address\Filament\Resources\Countries\Schemas\CountryForm;
use Capell\Address\Filament\Resources\Countries\Tables\CountriesTable;
use Capell\Address\Models\Country;
use Capell\Address\Providers\AddressServiceProvider;
use Capell\Admin\Filament\Concerns\HasFormConfigurator;
use Capell\Admin\Filament\Concerns\HasTableConfigurator;
use Capell\Core\Facades\CapellCore;
use Capell\Navigation\Filament\Concerns\HasNavigationBadge;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Override;

class CountryResource extends Resource
{
    use HasFormConfigurator;
    use HasNavigationBadge;
    use HasTableConfigurator;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Flag;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $formConfigurator = CountryForm::class;

    protected static string $tableConfigurator = CountriesTable::class;

    protected static bool $shouldRegisterNavigation = false;

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

    /**
     * @return class-string<Country>
     */
    #[Override]
    public static function getModel(): string
    {
        return Country::class;
    }

    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-address::navigation.countries'));
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_administration'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(AddressServiceProvider::$packageName)->isInstalled();
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'creator',
                'editor',
            ])
            ->withCount('addresses')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCountries::route('/'),
        ];
    }
}
