# Theme Studio Core Credits And Acknowledgements

Theme Studio Core is part of the Capell package set. This page names the main frameworks, packages, authors, and services this package leans on, with a short note about what they make possible here. It is intentionally shorter than the repository-wide credits page and closer to the package itself.

Package role: Core contracts, registry, preview context, and rendering support for Capell Theme Studio

## Shared Foundations

- [Laravel](https://laravel.com), created by [Taylor Otwell](https://github.com/taylorotwell), gives this package routing, service providers, Eloquent, validation, queues, events, auth, caching, and the normal Laravel testing surface.
- [Composer](https://getcomposer.org), [Packagist](https://packagist.org), and [GitHub](https://github.com) make the package install, split, and release workflow possible. Composer and Packagist deserve a special nod because Capell packages live and update through Composer metadata.
- [Blade](https://laravel.com/docs/blade) keeps package views close to Laravel, easy to override, and friendly to theme packages.
- [Tailwind CSS](https://tailwindcss.com), by [Tailwind Labs](https://tailwindcss.com), gives package themes and frontend views a shared styling language.
- [Vite](https://vite.dev), by [Evan You](https://github.com/yyx990803) and the Vite team, keeps package asset builds fast and predictable.
- [Pest](https://pestphp.com), [Orchestra Testbench](https://packages.tools/testbench), [PHPStan](https://phpstan.org), [Larastan](https://github.com/larastan/larastan), [Laravel Pint](https://laravel.com/docs/pint), and [Rector](https://getrector.com) keep this package easier to test, review, and update when bugs are fixed.

## Capell Packages Used Here

- [Capell Core](https://docs.capell.app) supplies the Capell-side contracts, surfaces, or runtime that Theme Studio Core builds on.
- [Capell Frontend](https://docs.capell.app) supplies the Capell-side contracts, surfaces, or runtime that Theme Studio Core builds on.

## Open-source Packages And Authors

- [Laravel Actions](https://github.com/lorisleiva/laravel-actions), by Loris Leiva, keeps package behaviour in small action classes instead of burying it in pages, commands, or controllers.
- [Spatie Laravel Data](https://github.com/spatie/laravel-data), by Ruben Van Assche and Spatie, keeps request state, settings, and package results typed at the boundaries.

## What We Especially Appreciate

Theme Studio Core is useful because it keeps the renderer contracts and preview context below the admin UI. That makes renderer bugs easier to isolate and new themes easier to add.

## Keeping This Page Current

When Theme Studio Core adds a new framework, service, or third-party package that becomes part of the user-facing workflow, update this page and the package README together. Credits should explain the practical help we get from a dependency, not just list a package name.
