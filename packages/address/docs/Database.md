# Database Reference — Capell Address

## Migrations

| File | Effect |
| --- | --- |
| `database/migrations/create_countries_table.php` | Creates `countries` |
| `database/migrations/create_addresses_table.php` | Creates `addresses` |

Run them via `php artisan capell:address-install`.

## `countries`

Key columns: `id`, `name`, `iso2`, `iso3`, `language_id` (FK), `default` (boolean), `status`, `meta` (JSON), userstamps, timestamps, soft deletes.

Indexes on `default` and `status` for scope lookups.

`Country` model traits: `HasDefault`, `HasFactory`, `HasStatus`, `HasUserstamps`, `HasJsonRelationships`.

Scopes: `ordered()`, `default()`, `enabled()`, `disabled()`.

Relationships: `language()` (`BelongsTo`), `languages()` (`BelongsToJson`).

## `addresses`

Key columns: `id`, `name`, `line1`, `line2`, `city`, `state`, `postal_code`, `country_id` (FK), `default` (boolean), `status`, `meta` (JSON), userstamps, timestamps, soft deletes.

Indexes: `address_part_index`, `address_full_index` (composite over common query columns), plus simple indexes on `city`, `state`, `postal_code`.

`Address` model traits: `HasDefault`, `HasFactory`, `HasStatus`, `HasUserstamps`, `HasJsonRelationships`.

Casts: `meta` → array, `default` → boolean, `status` → the Capell status enum.

Accessor: `full_address` — a single-line human-readable address.

Static helper: `Address::findAddress($criteria)` — look up an address by its component parts (see source for the match rules).

Relationships: `country()` (`BelongsTo`), `sites()` (`HasMany` resolved via `meta->address_id` on the sites table).

## Site relations (registered at runtime)

Attached in `AddressServiceProvider::registerRelationships()` via `Site::resolveRelationUsing(...)`:

- `Site::address()` — `BelongsTo(Address::class, 'meta->address_id')`
- `Site::country()` — `HasOneThrough(Country::class, Address::class)`

The `sites` table is **not** altered; `address_id` is stored inside the existing `meta` JSON.

## Factories

- `database/factories/CountryFactory.php`
- `database/factories/AddressFactory.php`

Use them in tests and seeders to generate rows quickly.
