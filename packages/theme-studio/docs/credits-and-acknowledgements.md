# Theme Studio Credits And Acknowledgements

Theme Studio is part of the Capell package set. This page names the main frameworks, packages, authors, and services this package leans on, with a short note about what they make possible here. It is intentionally shorter than the repository-wide credits page and closer to the package itself.

Package role: Commercial theme system bundle for Capell CMS

## Shared Foundations

- [Laravel](https://laravel.com), created by [Taylor Otwell](https://github.com/taylorotwell), gives this package routing, service providers, Eloquent, validation, queues, events, auth, caching, and the normal Laravel testing surface.
- [Filament](https://filamentphp.com) and the [Filament project](https://github.com/filamentphp/filament) give this package admin resources, pages, widgets, forms, tables, actions, and panel integration.
- [Blade](https://laravel.com/docs/blade) keeps package views close to Laravel, easy to override, and friendly to theme packages.
- [Tailwind CSS](https://tailwindcss.com), by [Tailwind Labs](https://tailwindcss.com), gives package themes and frontend views a shared styling language.
- [Vite](https://vite.dev), by [Evan You](https://github.com/yyx990803) and the Vite team, keeps package asset builds fast and predictable.
- [Composer](https://getcomposer.org), [Packagist](https://packagist.org), and [GitHub](https://github.com) make the package install, split, and release workflow possible. Composer and Packagist deserve a special nod because Capell packages live and update through Composer metadata.
- [Pest](https://pestphp.com), [Orchestra Testbench](https://packages.tools/testbench), [PHPStan](https://phpstan.org), [Larastan](https://github.com/larastan/larastan), [Laravel Pint](https://laravel.com/docs/pint), and [Rector](https://getrector.com) keep this package easier to test, review, and update when bugs are fixed.

## Capell Packages Used Here

- [Theme Agency](../../theme-agency/README.md) supplies the Capell-side contracts, surfaces, or runtime that Theme Studio builds on.
- [Theme Corporate](../../theme-corporate/README.md) supplies the Capell-side contracts, surfaces, or runtime that Theme Studio builds on.
- [Theme SaaS](../../theme-saas/README.md) supplies the Capell-side contracts, surfaces, or runtime that Theme Studio builds on.
- [Theme Studio Admin](../../theme-studio-admin/README.md) supplies the Capell-side contracts, surfaces, or runtime that Theme Studio builds on.
- [Theme Studio Core](../../theme-studio-core/README.md) supplies the Capell-side contracts, surfaces, or runtime that Theme Studio builds on.

## What We Especially Appreciate

Theme Studio is useful because it bundles the renderer set as one install while keeping the child packages independent. Composer does the grouping, and bug fixes can still land in the right package.

## Keeping This Page Current

When Theme Studio adds a new framework, service, or third-party package that becomes part of the user-facing workflow, update this page and the package README together. Credits should explain the practical help we get from a dependency, not just list a package name.
