# Capell Deployments

`capell-app/deployments` owns repository deployment connections and Composer requirement publishing for Capell CMS.

It supports marketplace-driven installs by connecting Capell to a Git provider and publishing Composer changes through pull requests.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)

**Open-source packages used here**

- [Laravel Actions](https://github.com/lorisleiva/laravel-actions) - single-purpose action classes that keep package workflows out of controllers and Filament resources.
- [Spatie Laravel Data](https://github.com/spatie/laravel-data) - typed data objects for package boundaries, form state, settings, and structured results.
- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) - Laravel package bootstrapping for config, migrations, commands, translations, and service provider setup.

**Linked package previews**

[![Laravel Actions GitHub preview](https://opengraph.githubassets.com/capell-readme/lorisleiva/laravel-actions)](https://github.com/lorisleiva/laravel-actions)

[![Spatie Laravel Data GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-data)](https://github.com/spatie/laravel-data)

[![Spatie Laravel Package Tools GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-package-tools)](https://github.com/spatie/laravel-package-tools)

## Install

```bash
composer require capell-app/deployments
```

## Key Surfaces

- Provider: `Capell\Deployments\Providers\DeploymentsServiceProvider`
- Admin page: `DeploymentConnectionPage`
- Composer publishing contract: `PublishesComposerChanges`
- Git providers: GitHub, GitLab, and Bitbucket implementations

## Package Docs

- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)

## Tests

```bash
vendor/bin/pest packages/deployments/tests
```
