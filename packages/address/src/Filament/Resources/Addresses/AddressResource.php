<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Resources\Addresses;

use BackedEnum;
use Capell\Address\Filament\Resources\Addresses\Pages\ManageAddresses;
use Capell\Address\Filament\Resources\Addresses\Schemas\AddressForm;
use Capell\Address\Filament\Resources\Addresses\Tables\AddressesTable;
use Capell\Address\Models\Address;
use Capell\Address\Providers\AddressServiceProvider;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\Core\Facades\CapellCore;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Override;

class AddressResource extends Resource
{
    use HasConfiguredForm;
    use HasConfiguredTable;
    use HasNavigationBadge;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::MapPin;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $formConfigurator = AddressForm::class;

    protected static string $tableConfigurator = AddressesTable::class;

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
     * @return class-string<Address>
     */
    #[Override]
    public static function getModel(): string
    {
        return Address::class;
    }

    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-address::generic.addresses'));
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_website'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(AddressServiceProvider::$packageName)->isInstalled();
    }

    public static function canGloballySearch(): bool
    {
        return CapellCore::getPackage(AddressServiceProvider::$packageName)->isInstalled()
            && parent::canGloballySearch();
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'creator',
                'editor',
            ])
            ->withCount(['sites'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAddresses::route('/'),
        ];
    }
}
