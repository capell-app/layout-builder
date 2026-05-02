# API Reference — Capell Address

Browse `src/` for full source. This page is a map of the key entry points.

## Service provider

- `src/Providers/AddressServiceProvider.php` — registers models, relationships (via `Site::resolveRelationUsing`), resources, schemas, schema extenders, and Blade components.

## Models

- `src/Models/Country.php` — `countries` table
- `src/Models/Address.php` — `addresses` table

See [address-database.md](address-database.md) for columns, traits, casts, and scopes.

## Filament resources

### Countries (`src/Filament/Resources/Countries/`)

- `CountryResource.php`
- `ManageCountries.php` (single-page resource)
- `CountriesTable.php`, `CountryForm.php`
- `DefaultCountrySchema.php`

### Addresses (`src/Filament/Resources/Addresses/`)

- `AddressResource.php`
- `ManageAddresses.php`
- `AddressesTable.php`, `AddressForm.php`
- `DefaultAddressSchema.php`

## Form components

- `src/Filament/Components/Forms/CountrySelect.php`
- `src/Filament/Components/Forms/AddressSelect.php`

Drop these directly into any Filament form:

```php
use Capell\Address\Filament\Components\Forms\AddressSelect;
use Capell\Address\Filament\Components\Forms\CountrySelect;

CountrySelect::make('country_id');
AddressSelect::make('address_id');
```

## Schema extender

- `src/Filament/Resources/Sites/Schemas/Extenders/SiteSchemaExtender.php` — implements the core `Extenders\SiteSchemaExtender` interface. Injects `AddressSelect` into `extendSiteMetaDetailsComponents()`, which runs for both create and edit Site forms.

## Model registrar

- `src/AddressModelRegistrar.php` — registers `Country` and `Address` with Capell's model registry (used by the workspace and type systems).

## Enums

- `src/Enums/*` — keys for schemas, resources, and component registrations.

## Commands

Under `src/Console/Commands/`:

- `InstallCommand` — `capell:address-install`
- `DemoCommand` — `capell:address-demo`

## Composer dependencies

- `capell-app/admin`

## Quick links

- Source directory: [`./src`](../src)
- Database reference: [address-database.md](address-database.md)
- Package README: [../README.md](../README.md)
