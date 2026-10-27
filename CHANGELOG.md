# Changelog

All notable changes to `capell-app/layout-builder` will be documented in this file.

## Unreleased

- Render saved catalogue builder blocks safely in public Blade output, including owner-scoped Spatie logo and team images.
- Fixed installed-runtime registration in roles that exclude authoring providers (such as the public role): Layout Builder no longer aborts on the unbound Admin runtime activator, so its public fragment and lazy widget routes are registered and no registration failure is logged.
- Blueprint selectors use the registered subject so unrelated blueprint types are not offered or selected by default.

- Split `BuildLayoutContentInventoryAction` into a coordinator plus focused content-inventory item projection and grouping collaborators.
- Added direct coverage for `PersistLayoutBuilderStateAction`, including page assignment, preview invalidation, widget-asset callback execution, and transaction rollback.
- Added a static notification translation guard so Layout Builder editor notifications cannot regress to inline English or raw exception messages.
- Removed the unused no-op `ApplyLayoutPlanAction` and added package-local coverage for the compatibility `InstallPackageAction` used by consuming package setup helpers.
- Removed the unused `CapellLayoutCacheKeyEnum` so future cache work follows the active `LayoutLoader` cache path.
- Reconciled `docs/screenshots.json` with committed light/dark layout-builder screenshots and added manifest tests that require referenced screenshots to exist.

- Prepared package metadata and documentation for ongoing Capell 0.0.x package work.

## 2026-06-03

- Replaced the stub critical health check with real diagnostics that verify the layout storage tables exist, the public layout graph builder contract is bound to the package implementation, and the dual-mode editor Livewire component is registered.
- Aligned `capell.json` `dependencies.requires` with `composer.json` by adding the `capell-app/admin` and `capell-app/block-library` requirements the package already hard-imports.
- Rewrote the marketplace summary, top-level description, and composer description to be benefit-led, and promoted five committed product screenshots into the marketplace manifest.
