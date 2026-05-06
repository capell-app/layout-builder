# Dashboard Reports Credits And Acknowledgements

Dashboard Reports is part of the Capell package set. This page names the main frameworks, packages, authors, and services this package leans on, with a short note about what they make possible here. It is intentionally shorter than the repository-wide credits page and closer to the package itself.

Package role: Generic CMS reporting widgets for Capell dashboards.

## Shared Foundations

- [Laravel](https://laravel.com), created by [Taylor Otwell](https://github.com/taylorotwell), gives this package routing, service providers, Eloquent, validation, queues, events, auth, caching, and the normal Laravel testing surface.
- [Filament](https://filamentphp.com) and the [Filament project](https://github.com/filamentphp/filament) give this package admin resources, pages, widgets, forms, tables, actions, and panel integration.
- [Composer](https://getcomposer.org), [Packagist](https://packagist.org), and [GitHub](https://github.com) make the package install, split, and release workflow possible. Composer and Packagist deserve a special nod because Capell packages live and update through Composer metadata.
- [Pest](https://pestphp.com), [Orchestra Testbench](https://packages.tools/testbench), [PHPStan](https://phpstan.org), [Larastan](https://github.com/larastan/larastan), [Laravel Pint](https://laravel.com/docs/pint), and [Rector](https://getrector.com) keep this package easier to test, review, and update when bugs are fixed.

## Capell Packages Used Here

- [Capell Admin](https://docs.capell.app) supplies the Capell-side contracts, surfaces, or runtime that Dashboard Reports builds on.
- [Capell Core](https://docs.capell.app) supplies the Capell-side contracts, surfaces, or runtime that Dashboard Reports builds on.

## Open-source Packages And Authors

- [Laravel Actions](https://github.com/lorisleiva/laravel-actions), by Loris Leiva, keeps package behaviour in small action classes instead of burying it in pages, commands, or controllers.
- [Spatie Laravel Data](https://github.com/spatie/laravel-data), by Ruben Van Assche and Spatie, keeps request state, settings, and package results typed at the boundaries.
- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools), by Freek Van der Herten and Spatie, keeps service provider setup, config publishing, migrations, and command registration predictable.

## What We Especially Appreciate

Dashboard Reports is intentionally plain. Its value is a shared widget shape for reporting packages, which makes bug fixes to report query conventions easier to apply across the operations bundle.

## Keeping This Page Current

When Dashboard Reports adds a new framework, service, or third-party package that becomes part of the user-facing workflow, update this page and the package README together. Credits should explain the practical help we get from a dependency, not just list a package name.
