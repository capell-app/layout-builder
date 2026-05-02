# Capell Address

**Product group:** Capell Foundation
**Tier:** Free

Capell Address adds country and address management to Capell. It is useful for branch pages, local business schema, contact pages, offices, venues, and any package that needs reusable address fields.

![Address hero banner](./HERO_BANNER.svg)

## When to install it

Install Address when sites or content records need structured location data instead of plain text address fields.

## Quick install

```bash
composer require capell-app/address
php artisan capell:address-install
php artisan capell:address-demo
```

## What appears in the admin

| Area          | What editors can do                                              |
| ------------- | ---------------------------------------------------------------- |
| Countries     | Manage country records                                           |
| Addresses     | Manage reusable address records                                  |
| Site settings | Attach address and country data through the Site schema extender |

## What developers get

- `CountrySelect` and `AddressSelect` Filament form components.
- Runtime relationships such as `Site::address()` and `Site::country()`.
- Address data stored in the site's `meta` JSON so the core `sites` table stays untouched.
- Factories for demo content and package tests.

## Example usage

```php
$site = Site::find(1);

echo $site->address?->full_address;
echo $site->country?->iso2;
```

## Deeper docs

- [Hosted documentation](https://docs.capell.app/packages/address/)
- [Database reference](docs/address-database.md)
- [API reference](docs/address-api.md)
