# Capell Deployments

`capell-app/deployments` owns repository deployment connections and Composer requirement publishing for Capell CMS.

It supports marketplace-driven installs by connecting Capell to a Git provider and publishing Composer changes through pull requests.

## Install

```bash
composer require capell-app/deployments
```

## Key Surfaces

- Provider: `Capell\Deployments\Providers\DeploymentsServiceProvider`
- Admin page: `DeploymentConnectionPage`
- Composer publishing contract: `PublishesComposerChanges`
- Git providers: GitHub, GitLab, and Bitbucket implementations

## Tests

```bash
vendor/bin/pest packages/deployments/tests
```
