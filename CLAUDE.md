# Claude Code Guidelines for Capell Packages

Optional add-on packages for the Capell CMS. Companion to `capell-app/capell` (`../capell-4`).

Frontend authoring safety is mandatory: non-admin frontend users must never receive editor HTML, JavaScript, metadata, markers, model IDs, field paths, selectors, or signed URLs. The page must load as ordinary public HTML; in-page authoring is discovered and rendered only after an authenticated admin beacon response.

## Non-negotiables

- `declare(strict_types=1);` in every PHP file.
- PHP 8.2 only — no typed class constants, no readonly classes, no DNF types.
- No single-letter or cryptic variable names — closures, migrations, example prose included.
- All closures must declare parameter and return types explicitly.
- No `php artisan` in this repo — use `vendor/bin/pest` directly.
- User-facing strings via `__('capell-...')`. Filament labels via method overrides, never static string properties.

## Architecture: Actions + Data

**All domain logic in Actions** (`packages/{pkg}/src/Actions/`, suffix `VerbNounAction`):

- Single `handle()` method. Extend `Lorisleiva\Actions\Action` or use `AsObject`.
- Components, resources, commands call `::run()` — no logic inside them.

**Structured data across boundaries** (`packages/{pkg}/src/Data/`, suffix `Data`):

- Inbound: `Data::from($request)`. Outbound: form state, wire-props, view models.
- Model JSON columns cast via `AsData` / `AsDataCollection`. No bare arrays across layers.

**Enums** (`packages/{pkg}/src/Enums/`):

- Backed enums for persisted values. Implement `HasLabel` for Filament options — never inline arrays.
- PascalCase multi-word cases; UPPER_SNAKE_CASE for status flags only.

## Packages

| Package           | Namespace               | Depends on                                |
| ----------------- | ----------------------- | ----------------------------------------- |
| `layout-builder`  | `Capell\LayoutBuilder`  | core, admin, frontend                     |
| `blog`            | `Capell\Blog`           | core, admin, frontend, **layout-builder** |
| `address`         | `Capell\Address`        | core, admin                               |
| `ai-orchestrator` | `Capell\AIOrchestrator` | core, admin                               |

**Blog requires LayoutBuilder — install LayoutBuilder first.**

## Package boundaries

- **Core must never import plugin classes** — no `use Capell\Blog\...` from Core. Use events or string command names for cross-plugin coordination.
- Packages must not reach into each other's internals (Arch tests enforce this).
- Minimize inter-package dependencies; only add what's truly needed.

## Extension points (use these, don't bypass them)

| Need                                | How                                                                                               |
| ----------------------------------- | ------------------------------------------------------------------------------------------------- |
| Register type / schema / widget     | `CapellCore::registerPageType\|registerSchema\|registerWidget()` in `ServiceProvider::register()` |
| Inject form fields                  | Implement `PageSchemaExtender`, tag with `PageSchemaExtender::TAG`                                |
| Lifecycle events / validation gates | `CapellAdmin::register()` / `subscribe()` / `ValidationSubscriber`                                |
| Inject HTML into Blade              | `RenderHookRegistry::register(RenderHookLocation::X, ...)`                                        |
| Package settings                    | `SettingsSchemaRegistry::register()` + `registerSettingsClass()`                                  |

Auto-discovered: types in `src/Types/`, schemas in `src/Schemas/`, widgets in `src/Widgets/`.

## PublishingStudio / Draftable

Any model in draft/publish must implement `Capell\Core\Contracts\Draftable` and register in the morph map. Reuse `ReplicateModelAction`, `ReplicatePageAction` — don't reinvent replication.

## Database

- Migrations in `packages/{pkg}/database/migrations/`.
- Settings migrations in `database/settings/`, registered in `InstallCommand`, wrapped in `exists()` checks.
- Writes go through Actions, not model methods.

## Testing

- Test actions directly: `MyAction::run($input)` — not through HTTP.
- Run single package: `vendor/bin/pest packages/layout-builder/tests`
- Minimum 80% coverage. Full suite: `composer test`.

## Composer local overlay

- Common issue: if a package test case class is not found, check `composer.local.json` as well as `composer.json`. The local overlay often needs matching `autoload` and `autoload-dev` PSR-4 entries for package namespaces, then regenerate with `COMPOSER=composer.local.json composer dump-autoload --no-scripts`.

## Commands

| Command                                    | Purpose                      |
| ------------------------------------------ | ---------------------------- |
| `composer test`                            | Pest tests (parallel)        |
| `composer preflight`                       | Rector + Pint + PHPStan      |
| `composer lint`                            | Pint only                    |
| `composer analyze`                         | PHPStan only                 |
| `composer prepare`                         | Seed demo workbench          |
| `composer serve`                           | Build + serve localhost:8000 |
| `vendor/bin/pest packages/{package}/tests` | Single package tests         |

## Git

1. `composer test` — 100% pass before committing.
2. `composer preflight` — clean before committing.
3. Verify in demo workbench (`composer serve`).
4. Commit immediately after task completion.
5. Branch naming: `feat/`, `fix/`, `docs/`, `chore/`. Target: `4.x`.

## gstack (REQUIRED — global install)

**Before doing ANY work, verify gstack is installed:**

```bash
test -d ~/.claude/skills/gstack/bin && echo "GSTACK_OK" || echo "GSTACK_MISSING"
```

If GSTACK_MISSING: STOP. Do not proceed. Tell the user:

> gstack is required for all AI-assisted work in this repo.
> Install it:
>
> ```bash
> git clone --depth 1 https://github.com/garrytan/gstack.git ~/.claude/skills/gstack
> cd ~/.claude/skills/gstack && ./setup --team
> ```
>
> Then restart your AI coding tool.

Do not skip skills, ignore gstack errors, or work around missing gstack.

Using gstack skills: After install, skills like /qa, /ship, /review, /investigate,
and /browse are available. Use /browse for all web browsing.
Use ~/.claude/skills/gstack/... for gstack file paths (the global path).
