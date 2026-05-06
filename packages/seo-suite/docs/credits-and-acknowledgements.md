# SEO Suite Credits And Acknowledgements

SEO Suite is part of the Capell package set. This page names the main frameworks, packages, authors, and services this package leans on, with a short note about what they make possible here. It is intentionally shorter than the repository-wide credits page and closer to the package itself.

Package role: SEO tools for Capell (sitemaps, structured data, social meta, AI ai-orchestrator)

## Shared Foundations

- [Laravel](https://laravel.com), created by [Taylor Otwell](https://github.com/taylorotwell), gives this package routing, service providers, Eloquent, validation, queues, events, auth, caching, and the normal Laravel testing surface.
- [Filament](https://filamentphp.com) and the [Filament project](https://github.com/filamentphp/filament) give this package admin resources, pages, widgets, forms, tables, actions, and panel integration.
- [Blade](https://laravel.com/docs/blade) keeps package views close to Laravel, easy to override, and friendly to theme packages.
- [Tailwind CSS](https://tailwindcss.com), by [Tailwind Labs](https://tailwindcss.com), gives package themes and frontend views a shared styling language.
- [Vite](https://vite.dev), by [Evan You](https://github.com/yyx990803) and the Vite team, keeps package asset builds fast and predictable.
- [Composer](https://getcomposer.org), [Packagist](https://packagist.org), and [GitHub](https://github.com) make the package install, split, and release workflow possible. Composer and Packagist deserve a special nod because Capell packages live and update through Composer metadata.
- [Pest](https://pestphp.com), [Orchestra Testbench](https://packages.tools/testbench), [PHPStan](https://phpstan.org), [Larastan](https://github.com/larastan/larastan), [Laravel Pint](https://laravel.com/docs/pint), and [Rector](https://getrector.com) keep this package easier to test, review, and update when bugs are fixed.

## Capell Packages Used Here

- [Capell Admin](https://docs.capell.app) supplies the Capell-side contracts, surfaces, or runtime that SEO Suite builds on.
- [Insights](../../insights/README.md) supplies the Capell-side contracts, surfaces, or runtime that SEO Suite builds on.
- [Capell Frontend](https://docs.capell.app) supplies the Capell-side contracts, surfaces, or runtime that SEO Suite builds on.

## Open-source Packages And Authors

- [PHP Sitemap Generator](https://github.com/icamys/php-sitemap-generator), by Dmitrii Prisacari, handles sitemap generation so SEO Suite can focus on Capell content and publishing rules.
- [Prism PHP](https://github.com/prism-php/prism), by TJ Miller, gives SEO Suite an AI provider layer for metadata help without tying the package to one provider.

## What We Especially Appreciate

SEO Suite is useful because sitemap generation, social metadata, structured data, and AI-assisted metadata share the same package boundary. Fixes to publishing gates or generated metadata stay close to SEO concerns.

## Keeping This Page Current

When SEO Suite adds a new framework, service, or third-party package that becomes part of the user-facing workflow, update this page and the package README together. Credits should explain the practical help we get from a dependency, not just list a package name.
