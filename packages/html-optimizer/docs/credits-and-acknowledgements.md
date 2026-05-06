# HTML Optimizer Credits And Acknowledgements

HTML Optimizer is part of the Capell package set. This page names the main frameworks, packages, authors, and services this package leans on, with a short note about what they make possible here. It is intentionally shorter than the repository-wide credits page and closer to the package itself.

Package role: HTML minification for Capell frontend rendering and page cache writes.

## Shared Foundations

- [Laravel](https://laravel.com), created by [Taylor Otwell](https://github.com/taylorotwell), gives this package routing, service providers, Eloquent, validation, queues, events, auth, caching, and the normal Laravel testing surface.
- [Composer](https://getcomposer.org), [Packagist](https://packagist.org), and [GitHub](https://github.com) make the package install, split, and release workflow possible. Composer and Packagist deserve a special nod because Capell packages live and update through Composer metadata.
- [Blade](https://laravel.com/docs/blade) keeps package views close to Laravel, easy to override, and friendly to theme packages.
- [Tailwind CSS](https://tailwindcss.com), by [Tailwind Labs](https://tailwindcss.com), gives package themes and frontend views a shared styling language.
- [Vite](https://vite.dev), by [Evan You](https://github.com/yyx990803) and the Vite team, keeps package asset builds fast and predictable.
- [Pest](https://pestphp.com), [Orchestra Testbench](https://packages.tools/testbench), [PHPStan](https://phpstan.org), [Larastan](https://github.com/larastan/larastan), [Laravel Pint](https://laravel.com/docs/pint), and [Rector](https://getrector.com) keep this package easier to test, review, and update when bugs are fixed.

## Capell Packages Used Here

- [Capell Frontend](https://docs.capell.app) supplies the Capell-side contracts, surfaces, or runtime that HTML Optimizer builds on.

## Open-source Packages And Authors

- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools), by Freek Van der Herten and Spatie, keeps service provider setup, config publishing, migrations, and command registration predictable.
- [voku HtmlMin](https://github.com/voku/HtmlMin), by Lars Moelleken, does the HTML minification work before cached frontend output is served.

## What We Especially Appreciate

The value here is felt in page delivery rather than in the admin. HtmlMin trims cached HTML output, and keeping that behaviour isolated makes performance fixes safer.

## Keeping This Page Current

When HTML Optimizer adds a new framework, service, or third-party package that becomes part of the user-facing workflow, update this page and the package README together. Credits should explain the practical help we get from a dependency, not just list a package name.
