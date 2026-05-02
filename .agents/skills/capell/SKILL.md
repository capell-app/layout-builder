---
name: capell
description: Capell Packages coding standards, architecture rules, and package conventions. Use when writing or reviewing any code in capell-packages-4.
---

# Capell Packages — Coding Skill

## Non-negotiables

- `declare(strict_types=1);` in every PHP file.
- PHP 8.2 only — no typed class constants, no readonly classes, no DNF types.
- No `php artisan` — use `vendor/bin/pest` directly; `orchestral/testbench` provides the Laravel context.
- No single-letter or cryptic variable names — ever, including closures and migrations.
- All closures must declare parameter types and return types explicitly (`: void` when mutating a passed-in object).

## Architecture: Actions + Data (reach for these first)

**All domain logic lives in Actions** (`packages/{pkg}/src/Actions/`):

- Suffix: `VerbNounAction` (`CreateBlogPostAction`, `PublishContentWidgetAction`).
- Single `handle()` method. Split by verb, never god-actions.
- Extend `Lorisleiva\Actions\Action` or use `AsObject` trait.
- Components, resources, commands call `::run()` — no domain logic inside them.

**Pass structured data across layer boundaries** (`packages/{pkg}/src/Data/`, suffix `Data`):

- Inbound: `Data::from($request)` — no `$request->input()` in actions.
- Outbound: Filament form state, Livewire wire-props, Blade view models.
- Model JSON/struct columns cast via `AsData` / `AsDataCollection`.
- No DTOs wrapping a single scalar.

**Enums** (`packages/{pkg}/src/Enums/`):

- Backed enums for persisted values (prefer string-backed).
- PascalCase multi-word cases; UPPER_SNAKE_CASE for status/state flags only.
- Implement `HasLabel` for Filament Select/Radio options — never inline option arrays.
- Type-hint enums in all signatures; never pass raw scalars where an enum exists.

## Packages in this repo

| Package     | Namespace          | Depends on                        |
| ----------- | ------------------ | --------------------------------- |
| `mosaic`    | `Capell\Mosaic`    | core, admin, frontend             |
| `blog`      | `Capell\Blog`      | core, admin, frontend, **mosaic** |
| `address`   | `Capell\Address`   | core, admin                       |
| `assistant` | `Capell\Assistant` | core, admin                       |

**Blog requires Mosaic — install Mosaic first.**

## Package boundaries (strict)

- **Core must never import plugin classes** — no `use Capell\Blog\...`, `use Capell\Mosaic\...` from Core.
- Cross-plugin coordination uses events, Artisan command name strings, or shared filesystem paths.
- Packages should not reach into each other's internals (Arch tests enforce this).

## Extension points — use these, don't bypass them

| Need                                   | Use                                                                                               |
| -------------------------------------- | ------------------------------------------------------------------------------------------------- |
| Register page type / schema / widget   | `CapellCore::registerPageType\|registerSchema\|registerWidget()` in `ServiceProvider::register()` |
| Inject Filament form fields            | Implement `PageSchemaExtender`, tag with `PageSchemaExtender::TAG`                                |
| Lifecycle callbacks / validation gates | `CapellAdmin::register()` / `subscribe()` / `ValidationSubscriber`                                |
| Inject HTML into Blade                 | `RenderHookRegistry::register(RenderHookLocation::X, ...)`                                        |
| Expose package settings                | `SettingsSchemaRegistry::register()` + `registerSettingsClass()`                                  |

Auto-discovered: types in `src/Types/`, schemas in `src/Schemas/`, widgets in `src/Widgets/`.

## Workspaces / Draftable

Any package model in draft/publish must implement `Capell\Core\Contracts\Draftable` and be registered in the morph map in the package's service provider. Reuse `ReplicateModelAction`, `ReplicatePageAction` — don't reinvent replication.

## Testing

- Test actions directly: `MyAction::run($input)` — not through HTTP.
- Run a single package: `vendor/bin/pest packages/mosaic/tests`
- Minimum 80% coverage. Full suite: `composer test`.
- Arch tests enforce package boundaries — don't suppress them.

## Commit checklist

1. `composer test` — 100% pass.
2. `composer preflight` — Rector + Pint + PHPStan clean.
3. Verify in demo workbench (`composer serve`) before committing.
4. No short variable names in the diff.
5. Commit immediately after task completion.

## Key commands

| Command                                    | Purpose                         |
| ------------------------------------------ | ------------------------------- |
| `composer test`                            | Pest tests (parallel)           |
| `composer preflight`                       | Rector + Pint + PHPStan         |
| `composer lint`                            | Pint only                       |
| `composer analyze`                         | PHPStan only                    |
| `composer prepare`                         | Seed demo workbench             |
| `composer serve`                           | Build + serve at localhost:8000 |
| `vendor/bin/pest packages/{package}/tests` | Run single package tests        |
