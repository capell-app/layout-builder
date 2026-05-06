# Login Audit Credits And Acknowledgements

Login Audit is part of the Capell package set. This page names the main frameworks, packages, authors, and services this package leans on, with a short note about what they make possible here. It is intentionally shorter than the repository-wide credits page and closer to the package itself.

Package role: Authentication log for Capell

## Shared Foundations

- [Laravel](https://laravel.com), created by [Taylor Otwell](https://github.com/taylorotwell), gives this package routing, service providers, Eloquent, validation, queues, events, auth, caching, and the normal Laravel testing surface.
- [Filament](https://filamentphp.com) and the [Filament project](https://github.com/filamentphp/filament) give this package admin resources, pages, widgets, forms, tables, actions, and panel integration.
- [Composer](https://getcomposer.org), [Packagist](https://packagist.org), and [GitHub](https://github.com) make the package install, split, and release workflow possible. Composer and Packagist deserve a special nod because Capell packages live and update through Composer metadata.
- [Pest](https://pestphp.com), [Orchestra Testbench](https://packages.tools/testbench), [PHPStan](https://phpstan.org), [Larastan](https://github.com/larastan/larastan), [Laravel Pint](https://laravel.com/docs/pint), and [Rector](https://getrector.com) keep this package easier to test, review, and update when bugs are fixed.

## Capell Packages Used Here

- [Capell Admin](https://docs.capell.app) supplies the Capell-side contracts, surfaces, or runtime that Login Audit builds on.

## Open-source Packages And Authors

- [Rappasoft Laravel Authentication Log](https://github.com/rappasoft/laravel-authentication-log), by Anthony Rappa, records login, logout, and failed-login events so Capell can show access history without writing that storage layer from scratch.
- [Tapp Network Filament Authentication Log](https://github.com/TappNetwork/filament-authentication-log), by Tapp Network, adds the Filament-facing authentication log UI that Capell can adapt for operators.

## What We Especially Appreciate

Login Audit is useful because it turns authentication events into admin evidence. The package wraps recording, tables, widgets, and settings so access-related bug fixes are not scattered through the app.

## Keeping This Page Current

When Login Audit adds a new framework, service, or third-party package that becomes part of the user-facing workflow, update this page and the package README together. Credits should explain the practical help we get from a dependency, not just list a package name.
