<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Resources\Addresses;

use BackedEnum;
use Capell\Address\Enums\ModelEnum;
use Capell\Address\Filament\Resources\Addresses\Pages\ManageAddresses;
use Capell\Address\Filament\Resources\Addresses\Schemas\AddressForm;
use Capell\Address\Filament\Resources\Addresses\Tables\AddressesTable;
use Capell\Address\Models\Address;
use Capell\Address\Providers\AddressServiceProvider;
use Capell\Admin\Filament\Concerns\HasFormConfigurator;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\Admin\Filament\Concerns\HasTableConfigurator;
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
    use HasFormConfigurator;
    use HasNavigationBadge;
    use HasTableConfigurator;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::MapPin;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $formConfigurator = AddressForm::class;

    protected static string $tableConfigurator = AddressesTable::class;

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
     * @return class-string<Address>
     */
    #[Override]
    public static function getModel(): string
    {
        return CapellCore::getModel(ModelEnum::Address);
    }

    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-address::generic.addresses'));
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_system'));
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
