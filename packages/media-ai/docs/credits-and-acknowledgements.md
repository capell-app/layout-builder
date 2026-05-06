# Media AI Credits And Acknowledgements

Media AI is part of the Capell package set. This page names the main frameworks, packages, authors, and services this package leans on, with a short note about what they make possible here. It is intentionally shorter than the repository-wide credits page and closer to the package itself.

Package role: Optional AI-assisted media actions for Capell.

## Shared Foundations

- [Laravel](https://laravel.com), created by [Taylor Otwell](https://github.com/taylorotwell), gives this package routing, service providers, Eloquent, validation, queues, events, auth, caching, and the normal Laravel testing surface.
- [Filament](https://filamentphp.com) and the [Filament project](https://github.com/filamentphp/filament) give this package admin resources, pages, widgets, forms, tables, actions, and panel integration.
- [Composer](https://getcomposer.org), [Packagist](https://packagist.org), and [GitHub](https://github.com) make the package install, split, and release workflow possible. Composer and Packagist deserve a special nod because Capell packages live and update through Composer metadata.
- [Pest](https://pestphp.com), [Orchestra Testbench](https://packages.tools/testbench), [PHPStan](https://phpstan.org), [Larastan](https://github.com/larastan/larastan), [Laravel Pint](https://laravel.com/docs/pint), and [Rector](https://getrector.com) keep this package easier to test, review, and update when bugs are fixed.

## Capell Packages Used Here

- [Capell Admin](https://docs.capell.app) supplies the Capell-side contracts, surfaces, or runtime that Media AI builds on.
- [Capell Core](https://docs.capell.app) supplies the Capell-side contracts, surfaces, or runtime that Media AI builds on.

## Open-source Packages And Authors

- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools), by Freek Van der Herten and Spatie, keeps service provider setup, config publishing, migrations, and command registration predictable.

## What We Especially Appreciate

Media AI is deliberately optional. The null image doctor keeps installs safe, while teams can bind a real implementation later and still receive fixes to the Filament action wrapper.

## Keeping This Page Current

When Media AI adds a new framework, service, or third-party package that becomes part of the user-facing workflow, update this page and the package README together. Credits should explain the practical help we get from a dependency, not just list a package name.
