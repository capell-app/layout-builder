<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Resources\Countries;

use BackedEnum;
use Capell\Address\Enums\ModelEnum;
use Capell\Address\Filament\Resources\Countries\Pages\ManageCountries;
use Capell\Address\Filament\Resources\Countries\Schemas\CountryForm;
use Capell\Address\Filament\Resources\Countries\Tables\CountriesTable;
use Capell\Address\Models\Country;
use Capell\Admin\Filament\Concerns\HasFormConfigurator;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\Admin\Filament\Concerns\HasTableConfigurator;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Core\Facades\CapellCore;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $recordTitleAttribute = 'name';

    /** @var class-string<FormConfigurator> */
    protected static string $formConfigurator = CountryForm::class;

    /** @var class-string<TableConfigurator> */
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
        return CapellCore::getModel(ModelEnum::Country);
    }

    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-admin::navigation.countries'));
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_system'));
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'creator',
                'editor',
            ])
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
