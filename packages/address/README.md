# Address

Status: **Available, schema-owning** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **admin** · Product group: **Capell Foundation**

## What This Plugin Adds

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

## Data Model

- countries stores localized country names with iso2 and iso3 codes.
- addresses stores line, city, state, postal code, and country relationship data.
- Countries connect to core languages.
- Deletion behaviour should be verified before documenting cascading rules.

## Install Impact

- Adds country and address admin navigation.
- Adds database tables for countries and addresses.
- Adds address/country form components for package developers.
- No public route is registered by this package.

## Commands

- `capell:address-demo {--sites=}` (packages/address/src/Console/Commands/DemoCommand.php)
- `capell:address-faker {--count=25} {--force}` (packages/address/src/Console/Commands/FakerCommand.php)
- `capell:address-install` (packages/address/src/Console/Commands/InstallCommand.php)

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

## Quick Start

1. Install the package with `composer require capell-app/address`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../layout-builder/README.md](../layout-builder/README.md)
- [../navigation/README.md](../navigation/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
