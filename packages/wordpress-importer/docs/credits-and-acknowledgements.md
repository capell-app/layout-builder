# WordPress Importer Credits And Acknowledgements

WordPress Importer is part of the Capell package set. This page names the main frameworks, packages, authors, and services this package leans on, with a short note about what they make possible here. It is intentionally shorter than the repository-wide credits page and closer to the package itself.

Package role: WordPress WXR import source for the Capell Migration AIOrchestrator.

## Shared Foundations

- [Laravel](https://laravel.com), created by [Taylor Otwell](https://github.com/taylorotwell), gives this package routing, service providers, Eloquent, validation, queues, events, auth, caching, and the normal Laravel testing surface.
- [Filament](https://filamentphp.com) and the [Filament project](https://github.com/filamentphp/filament) give this package admin resources, pages, widgets, forms, tables, actions, and panel integration.
- [Composer](https://getcomposer.org), [Packagist](https://packagist.org), and [GitHub](https://github.com) make the package install, split, and release workflow possible. Composer and Packagist deserve a special nod because Capell packages live and update through Composer metadata.
- [Pest](https://pestphp.com), [Orchestra Testbench](https://packages.tools/testbench), [PHPStan](https://phpstan.org), [Larastan](https://github.com/larastan/larastan), [Laravel Pint](https://laravel.com/docs/pint), and [Rector](https://getrector.com) keep this package easier to test, review, and update when bugs are fixed.

## Capell Packages Used Here

- [Capell Admin](https://docs.capell.app) supplies the Capell-side contracts, surfaces, or runtime that WordPress Importer builds on.
- [Capell Core](https://docs.capell.app) supplies the Capell-side contracts, surfaces, or runtime that WordPress Importer builds on.
- [Migration Assistant](../../migration-assistant/README.md) supplies the Capell-side contracts, surfaces, or runtime that WordPress Importer builds on.

## Open-source Packages And Authors

- [PHP SimpleXML](https://www.php.net/manual/en/book.simplexml.php), by the PHP project, parses WordPress WXR XML exports without adding a heavy importer dependency.
- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools), by Freek Van der Herten and Spatie, keeps service provider setup, config publishing, migrations, and command registration predictable.

## What We Especially Appreciate

WordPress Importer is useful because it reads WXR as a source format rather than pretending it owns the whole migration. XML parsing stays here while Migration Assistant owns mapping and execution.

## Keeping This Page Current

When WordPress Importer adds a new framework, service, or third-party package that becomes part of the user-facing workflow, update this page and the package README together. Credits should explain the practical help we get from a dependency, not just list a package name.
