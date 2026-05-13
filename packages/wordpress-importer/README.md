# Wordpress Importer

WordPress WXR import source for the Capell Migration AIOrchestrator.

## At A Glance

- Package: `capell-app/wordpress-importer`
- Namespace: `Capell\WordPressImporter\`
- Service providers: `packages/wordpress-importer/src/Providers/WordPressImporterServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`, `capell-app/migration-assistant`
- Third-party dependencies: `ext-simplexml`, `spatie/laravel-package-tools`

## What It Adds

- WordPress WXR import source for the Capell Migration AIOrchestrator.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)
- [Capell Migration Assistant](../migration-assistant/README.md)

**Open-source packages used here**

- [PHP SimpleXML extension](https://www.php.net/manual/en/book.simplexml.php) - the PHP XML reader used to parse WordPress WXR export files.
- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) - Laravel package bootstrapping for config, migrations, commands, translations, and service provider setup.

**Linked package previews**

[![Spatie Laravel Package Tools GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-package-tools)](https://github.com/spatie/laravel-package-tools)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

## Code Map

| Area      | Path                                        | Purpose                                                           |
| --------- | ------------------------------------------- | ----------------------------------------------------------------- |
| Providers | `packages/wordpress-importer/src/Providers` | Registration, extension hooks, routes, migrations, and resources. |
| Resources | `packages/wordpress-importer/resources`     | Views, translations, assets, and package resources.               |
| Tests     | `packages/wordpress-importer/tests`         | Package-level Pest coverage.                                      |

## Extension Points

- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Requires `capell-app/migration-assistant`, `capell-app/core`, `capell-app/admin`, and `ext-simplexml`.
- Registers a WordPress WXR source reader with MigrationAssistant.
- Does not add migrations; MigrationAssistant owns import sessions and rollback dashboard-dashboard_reports.

## Install And Setup

- Install with `composer require capell-app/wordpress-importer` in the host Capell application.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [overview.md](docs/overview.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/wordpress-importer/tests --configuration=phpunit.xml
```
