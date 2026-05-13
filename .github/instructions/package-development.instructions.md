---
applyTo: 'packages/**/*.php,packages/**/composer.json,composer.json,composer.local.json'
---

# Capell Package Development

- Follow sibling package structure before creating files or new package-level conventions.
- Package-local behavior belongs in `src/Actions`, `src/Data`, package services, models, and providers. App-level integration should install, configure, seed, and test the package without duplicating package logic.
- Register types, schemas, widgets, settings, render hooks, lifecycle subscribers, migrations, commands, and assets through package service providers and Capell registries.
- Keep package boundaries clean. Do not reach into another package's internals; use documented extension points, events, contracts, config, or command names.
- Update `composer.local.json` as well as `composer.json` when package namespaces, test namespaces, or local overlays change.
- After namespace or overlay changes, use `COMPOSER=composer.local.json composer dump-autoload --no-scripts` when local overlay discovery is the issue.
