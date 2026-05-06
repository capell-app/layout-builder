# Tags

Status: **Available, schema-owning** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **admin, console** · Product group: **Capell Foundation**

## What This Plugin Adds

Tags adds tag management, taggable relationships, a reusable tags input, and model traits for Capell content.

- Tag Filament resource.
- TagsInput form component.
- HasTags model concern.
- Tag and Taggable models.
- Install command and model registrar.

## Why It Matters

**For developers:** Provides a shared tagging layer that Blog and page-like models can use without each package defining its own tag tables.

**For teams:** Lets editors classify content consistently across articles and pages.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Navigation](../navigation/README.md)
- [Capell Publishing Studio](../publishing-studio/README.md)

**Open-source packages used here**

- [Filament Spatie Laravel Tags Plugin](https://github.com/filamentphp/spatie-laravel-tags-plugin) - Filament form integration for Spatie tags inside Capell tagging workflows.

**Linked package previews**

[![Filament Spatie Laravel Tags Plugin GitHub preview](https://opengraph.githubassets.com/capell-readme/filamentphp/spatie-laravel-tags-plugin)](https://github.com/filamentphp/spatie-laravel-tags-plugin)

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Tags admin index.
- Create/edit tag form.
- Tag relation manager showing tagged pages.
- Article or page form using TagsInput.

## Technical Shape

- TagsServiceProvider, AdminServiceProvider, and ConsoleServiceProvider register package surfaces.
- Migration alters/creates tag-related table support.
- Models: Tag and Taggable.
- Filament resource: TagResource.
- TagTypeEnum defines tag types.

## Data Model

- tags stores translated name and slug values plus type.
- taggables connects tags to articles, pages, and other taggable models.
- Tag model registrar handles morph/model integration.
- Deletion behaviour for taggables should be verified before removing shared tags.

## Install Impact

- Adds tag database changes.
- Adds tag admin navigation.
- Adds tags form component.
- No public route is registered by this package.

## Commands

- `capell:tags-install` (packages/tags/src/Console/Commands/InstallCommand.php)

## Admin And Access

- CreateTag (packages/tags/src/Filament/Resources/Tags/Pages/CreateTag.php)
- EditTag (packages/tags/src/Filament/Resources/Tags/Pages/EditTag.php)
- ListTags (packages/tags/src/Filament/Resources/Tags/Pages/ListTags.php)
- TagResource (packages/tags/src/Filament/Resources/Tags/TagResource.php)

- None proven in this package directory.

## Common Pitfalls

- Run the install command or migration before using TagsInput.
- Register taggable models before expecting relationships.
- Use typed tag categories rather than ad hoc strings.

## Quick Start

1. Install the package with `composer require capell-app/tags`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin surface or integration point and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../blog/README.md](../blog/README.md)
- [../layout-builder/README.md](../layout-builder/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
