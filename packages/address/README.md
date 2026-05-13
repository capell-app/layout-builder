# Address

Address adds reusable country, region, and address data structures for Capell forms and admin records.

## At A Glance

- Package: `capell-app/address`
- Namespace: `Capell\Address\`
- Surfaces: Filament admin, console, database
- Service providers: `packages/address/src/Providers/AddressServiceProvider.php`
- Capell dependencies: `capell-app/admin`
- Third-party dependencies: `stijnvanouplines/blade-country-flags`

## What It Adds

Address adds reusable countries, address records, address selectors, country selectors, and flag rendering to the Capell admin surface.

- Filament resources for countries and addresses.
- Address, country, and flag form components for other packages.
- Site schema extension support where address details are needed.
- Install, demo, and faker commands for local package data.

## Why It Matters

**For developers:** Provides Country and Address models, typed address metadata, Filament configurators, observers, and support classes for URL and flag rendering.

**For teams:** Keeps location data consistent across structured websites instead of duplicating country and address fields in separate features.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)

**Open-source packages used here**

- [Blade Country Flags](https://github.com/stijnvanouplines/blade-country-flags) - country flag Blade components used by address and locale-oriented admin fields.

**Linked package previews**

[![Blade Country Flags GitHub preview](https://opengraph.githubassets.com/capell-readme/stijnvanouplines/blade-country-flags)](https://github.com/stijnvanouplines/blade-country-flags)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Countries admin index.
- Addresses admin index.
- Create/edit country form.
- Create/edit address form.
- Site settings fields where address data is injected.

## Technical Shape

- AddressServiceProvider registers the package.
- Migrations create countries and addresses.
- Models: Country and Address.
- Filament resources: CountryResource and AddressResource.
- Form components: AddressSelect, CountrySelect, FlagSelect.
- Observers keep model state consistent.

## Code Map

| Area      | Path                             | Purpose                                                             |
| --------- | -------------------------------- | ------------------------------------------------------------------- |
| Data      | `packages/address/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/address/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/address/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/address/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Providers | `packages/address/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/address/resources`     | Views, translations, assets, and package resources.                 |
| Database  | `packages/address/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/address/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Resources: `AddressResource`, `CountryResource`.
- Pages: `ManageAddresses`, `ManageCountries`.

## Commands

- `capell:address-demo {--sites=}` (packages/address/src/Console/Commands/DemoCommand.php)
- `capell:address-faker {--count=25} {--force}` (packages/address/src/Console/Commands/FakerCommand.php)
- `capell:address-install` (packages/address/src/Console/Commands/InstallCommand.php)

## Data And Persistence

- countries stores localized country names with iso2 and iso3 codes.
- addresses stores line, city, state, postal code, and country relationship data.
- Countries connect to core languages.
- Deletion behaviour should be verified before documenting cascading rules.

- Models: `Address`, `Country`.
- Migrations: `2026_05_10_190839_01_create_countries_table.php`, `2026_05_10_190839_02_create_addresses_table.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds country and address admin navigation.
- Adds database tables for countries and addresses.
- Adds address/country form components for package developers.
- No public route is registered by this package.

## Install And Setup

- Install with `composer require capell-app/address` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Admin And Access

- AddressResource (packages/address/src/Filament/Resources/Addresses/AddressResource.php)
- ManageAddresses (packages/address/src/Filament/Resources/Addresses/Pages/ManageAddresses.php)
- CountryResource (packages/address/src/Filament/Resources/Countries/CountryResource.php)
- ManageCountries (packages/address/src/Filament/Resources/Countries/Pages/ManageCountries.php)

- None proven in this package directory.

## Common Pitfalls

- Run migrations before opening the resources.
- Seed or import countries before expecting useful address form-builder.
- Check language records before relying on localized country names.

## Docs

- [address-api.md](docs/address-api.md)
- [address-database.md](docs/address-database.md)
- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [overview.md](docs/overview.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/address/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
