# Translation Manager

File-based Laravel translation management for Capell and Filament admin panels.

## At A Glance

- Package: `capell-app/translation-manager`
- Namespace: `Capell\TranslationManager\`
- Surfaces: Filament admin
- Service providers: `packages/translation-manager/src/Providers/AdminServiceProvider.php`, `packages/translation-manager/src/Providers/TranslationManagerServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`
- Third-party dependencies: `lorisleiva/laravel-actions`, `spatie/laravel-data`, `spatie/laravel-package-tools`

## What It Adds

- File-based Laravel translation management for Capell and Filament admin panels.

## Code Map

| Area      | Path                                         | Purpose                                                             |
| --------- | -------------------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/translation-manager/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/translation-manager/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Filament  | `packages/translation-manager/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Providers | `packages/translation-manager/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/translation-manager/resources`     | Views, translations, assets, and package resources.                 |
| Config    | `packages/translation-manager/config`        | Package configuration and publishable config.                       |
| Tests     | `packages/translation-manager/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Pages: `TranslationManagerPage`.

## Data And Persistence

- Config: `packages/translation-manager/config/capell-translation-manager.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `TranslationAITranslator`, `TranslationFileStore`, `TranslationSourceResolver`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install And Setup

- Install with `composer require capell-app/translation-manager` in the host Capell application.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

- [overview.md](docs/overview.md)
- [sources-stores-and-ai.md](docs/sources-stores-and-ai.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/translation-manager/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
