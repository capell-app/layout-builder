# Redirect Manager

The redirects package manages 301 and 302 redirects, import/export, and automatic redirects when page slugs change. It keeps using Capell's existing `page_urls` table for redirect storage.

---

## Data model

The `page_urls` table stores redirects with:

| Column        | Purpose                                                                          |
| ------------- | -------------------------------------------------------------------------------- |
| `target_url`  | Destination URL for manual redirects (null for auto-generated slug history rows) |
| `status_code` | 301 or 302, backed by `RedirectStatusCodeEnum`                                   |
| `is_manual`   | Distinguishes user-created redirects from auto-generated ones                    |
| `hit_count`   | Number of times this redirect has fired                                          |
| `last_hit_at` | Last time the redirect fired                                                     |
| `notes`       | Optional admin notes                                                             |

Auto-generated redirects resolve through `pageable_id` and slug history. Manual redirects resolve through `target_url`.

## Admin resource

`RedirectResource` is a Filament single-page resource (`ManageRedirects`) scoped to `type = redirect`:

- Full CRUD with `RedirectForm` / `RedirectsTable` configurators.
- Table filters by site, language, status code, hit-count buckets.
- Site and language selectors on the form, defaulting to the current admin context.
- Import (CSV via Filament Importer) and Export (CSV via Filament Exporter).
- Permission-gated via `RedirectPolicy`, including dedicated `import` / `export` gates.

The redirects package registers this resource with Capell Admin from `RedirectsServiceProvider`.

## Validation

`ValidateRedirectAction` runs on every save and import row:

- **Duplicate detection** - source URL already has a redirect on the same site / language.
- **Self-redirect prevention** - `source_url === target_url`.
- **Loop detection** - follows the redirect chain up to 10 hops, aborting if it finds a cycle.
- **Chain warnings** - a redirect whose target is itself a redirect is allowed but surfaces a warning in the admin.

## CSV import / export

- **`RedirectImporter`** uses Filament's `Importer` base class with per-column mapping and automatic header detection. Site and language are picked once on the import screen and applied to every row. Failed rows are reported per-row without aborting the import.
- **`RedirectExporter`** outputs all manual-redirect columns, filterable by the same table filters.

## Frontend resolution

Frontend page resolution delegates redirect decisions to `RedirectResolver`. The default `PageUrlRedirectResolver` resolves active `page_urls` rows of `type = redirect` and issues a `301` or `302` according to the row's `status_code`. Hit count and `last_hit_at` are updated on every match.

## SEO Tools integration

SEO Tools records broken-link events and groups repeated failures into redirect opportunities. The Redirects package remains the persistence boundary:

- Editors review broken links and opportunity counts in SEO Tools.
- The "create redirect" flow opens a modal from the broken-link record, pre-fills the source URL, accepts a target URL and status code, and writes a normal manual redirect into `page_urls`.
- Redirect validation still runs through `ValidateRedirectAction`, including duplicate, self-redirect, loop, and chain checks.
- Hit count and `last_hit_at` continue to be owned by redirect resolution.

This keeps SEO analysis in SEO Tools while preserving the Redirect Manager as the single place that stores and validates redirects.

Redirect chain and loop badges are read from `redirect_health_snapshots`. Run `RefreshRedirectHealthSnapshotAction` after changing one redirect or `RefreshRedirectHealthSnapshotsAction` after imports.

## Configuration

```php
'auto_redirects' => [
    'enabled' => env('CAPELL_REDIRECTS_AUTO_ENABLED', true),
    'status_code' => 301,
],
```

## Related files

| Concern             | File                                                                                                                                                 |
| ------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| Resource            | `packages/foundation/redirects/src/Filament/Resources/Redirects/RedirectResource.php`                                                                |
| Page                | `packages/foundation/redirects/src/Filament/Resources/Redirects/Pages/ManageRedirects.php`                                                           |
| Form / Table        | `packages/foundation/redirects/src/Filament/Resources/Redirects/Schemas/RedirectForm.php`, `Tables/RedirectsTable.php`                               |
| Importer / Exporter | `packages/foundation/redirects/src/Filament/Imports/RedirectImporter.php`, `packages/foundation/redirects/src/Filament/Exports/RedirectExporter.php` |
| Policy              | `packages/foundation/redirects/src/Policies/RedirectPolicy.php`                                                                                      |
| Validation action   | `packages/foundation/redirects/src/Actions/ValidateRedirectAction.php`                                                                               |
| Automatic redirects | `packages/foundation/redirects/src/Actions/CreateAutomaticRedirectAction.php`                                                                        |
| Status enum         | `packages/foundation/redirects/src/Enums/RedirectStatusCodeEnum.php`                                                                                 |
