# Publishing Studio Credits And Acknowledgements

Publishing Studio is part of the Capell package set. This page names the main frameworks, packages, authors, and services this package leans on, with a short note about what they make possible here. It is intentionally shorter than the repository-wide credits page and closer to the package itself.

Package role: Editorial workflow package for Capell revisions, scheduling, approvals, and controlled publishing

## Shared Foundations

- [Laravel](https://laravel.com), created by [Taylor Otwell](https://github.com/taylorotwell), gives this package routing, service providers, Eloquent, validation, queues, events, auth, caching, and the normal Laravel testing surface.
- [Filament](https://filamentphp.com) and the [Filament project](https://github.com/filamentphp/filament) give this package admin resources, pages, widgets, forms, tables, actions, and panel integration.
- [Composer](https://getcomposer.org), [Packagist](https://packagist.org), and [GitHub](https://github.com) make the package install, split, and release workflow possible. Composer and Packagist deserve a special nod because Capell packages live and update through Composer metadata.
- [Pest](https://pestphp.com), [Orchestra Testbench](https://packages.tools/testbench), [PHPStan](https://phpstan.org), [Larastan](https://github.com/larastan/larastan), [Laravel Pint](https://laravel.com/docs/pint), and [Rector](https://getrector.com) keep this package easier to test, review, and update when bugs are fixed.

## Capell Packages Used Here

- [Capell Admin](https://docs.capell.app) supplies the Capell-side contracts, surfaces, or runtime that Publishing Studio builds on.
- [Migration Assistant](../../migration-assistant/README.md) supplies the Capell-side contracts, surfaces, or runtime that Publishing Studio builds on.
- [Capell Core](https://docs.capell.app) supplies the Capell-side contracts, surfaces, or runtime that Publishing Studio builds on.
- [Navigation](../../navigation/README.md) supplies the Capell-side contracts, surfaces, or runtime that Publishing Studio builds on.

## Open-source Packages And Authors

- [php-diff](https://github.com/jfcherng/php-diff), by Jack Cherng and Chris Boulton, turns content differences into reviewable output for publishing workflows.

## What We Especially Appreciate

Publishing Studio is useful because it keeps editorial safety in one lane. Previews, approvals, schedules, comparisons, comments, and rollback all get maintained together instead of living as separate admin hacks.

## Keeping This Page Current

When Publishing Studio adds a new framework, service, or third-party package that becomes part of the user-facing workflow, update this page and the package README together. Credits should explain the practical help we get from a dependency, not just list a package name.
