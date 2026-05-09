# Capell Translation Manager

Translation Manager adds a file-based Filament admin editor for Laravel translation files.

It edits local application language files directly and writes package/vendor changes to Laravel override files under `lang/vendor/{namespace}/{locale}` by default. Phase one is deliberately file-only: it does not create database tables or Capell `Language` records.

## Features

- Browse configured app, package, and vendor translation sources.
- Compare a source locale, normally English, against a target locale.
- Create blank locale files or duplicate an existing locale.
- Edit PHP array language files and JSON language files.
- Save package/vendor changes as override files instead of mutating vendor/package source.
- Optionally expose selected-key AI translation when an AI translator binding is available.

## Configuration

Publish or override `capell-translation-manager.php` to adjust source locale, source paths, locale validation, vendor namespaces, and whether any source is writable in place.
