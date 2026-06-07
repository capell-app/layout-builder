# Changelog

All notable changes to `capell-app/layout-builder` will be documented in this file.

## Unreleased

- Removed the unused no-op `ApplyLayoutPlanAction` and added package-local coverage for the compatibility `InstallPackageAction` used by consuming package setup helpers.
- Removed the unused `CapellLayoutCacheKeyEnum` so future cache work follows the active `LayoutLoader` cache path.
- Reconciled `docs/screenshots.json` with committed light/dark layout-builder screenshots and added manifest tests that require referenced screenshots to exist.

- Prepared package metadata and documentation for ongoing Capell 4.x package work.

## 2026-06-03

- Replaced the stub critical health check with real diagnostics that verify the layout storage tables exist, the public layout graph builder contract is bound to the package implementation, and the dual-mode editor Livewire component is registered.
- Aligned `capell.json` `dependencies.requires` with `composer.json` by adding the `capell-app/admin` and `capell-app/block-library` requirements the package already hard-imports.
- Rewrote the marketplace summary, top-level description, and composer description to be benefit-led, and promoted five committed product screenshots into the marketplace manifest.
