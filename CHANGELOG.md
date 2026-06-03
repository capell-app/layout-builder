# Changelog

All notable changes to `capell-app/layout-builder` will be documented in this file.

## Unreleased

- Prepared package metadata and documentation for ongoing Capell 4.x package work.

## 2026-06-03

- Replaced the stub critical health check with real diagnostics that verify the layout storage tables exist, the public layout graph builder contract is bound to the package implementation, and the dual-mode editor Livewire component is registered.
- Aligned `capell.json` `dependencies.requires` with `composer.json` by adding the `capell-app/admin` and `capell-app/block-library` requirements the package already hard-imports.
- Rewrote the marketplace summary, top-level description, and composer description to be benefit-led, and promoted five committed product screenshots into the marketplace manifest.
