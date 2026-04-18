# Claude Code Guidelines for Capell Packages

## Project Overview

**Capell Packages** is a separate repository containing additional, optional packages for the Capell CMS. These are add-on modules that extend Capell's functionality with specialized features beyond the core platform.

- **Repository**: https://github.com/capell-app/capell-packages
- **Main branch**: `4.x`
- **Type**: Monorepo (managed with Composer path repositories)
- **Relationship**: Companion to `capell-app/capell`

### What's Included

Optional packages that add specialized functionality:

- Mosaic and page design tools
- Blog/article content types
- Address/location management
- Custom widget libraries
- Integrations with third-party services

These packages are **not required** for Capell to run but provide specialized editorial and design capabilities.

## Tech Stack

| Component | Version    | Purpose                                       |
| --------- | ---------- | --------------------------------------------- |
| PHP       | 8.2+       | Backend language (8.2 only, no 8.3+ features) |
| Laravel   | 10.x+      | Web framework                                 |
| Filament  | 4.7+, 5.2+ | Admin panel integration                       |
| Pest      | 3.0+, 4.1+ | Testing framework                             |
| Pint      | ^1.25.0    | Code formatting (Laravel standard)            |
| PHPStan   | ^3.0       | Static analysis (level 5+)                    |
| Rector    | ^2.0       | Automated refactoring                         |
| Tailwind  | 4+         | CSS framework (demo & packages)               |
| Node.js   | 20+        | For Tailwind builds and npm tooling           |

## Development Setup

### Initial Setup

```bash
# Install dependencies
composer install
npm install

# Prepare the test environment
composer prepare

# Build and serve the demo/test workbench
composer serve
```

### Development Commands

| Command              | Purpose                                                 |
| -------------------- | ------------------------------------------------------- |
| `composer test`      | Run Pest unit tests                                     |
| `composer test:unit` | Run tests with parallel execution                       |
| `composer preflight` | Run all checks: Prettier, ESLint, Rector, Pint, PHPStan |
| `composer rector`    | Rector code refactoring                                 |
| `composer lint`      | Pint code style (Laravel Pint standard)                 |
| `composer analyze`   | PHPStan static analysis                                 |
| `composer prettier`  | Format Blade, CSS, JS, JSON, YAML, Markdown             |
| `composer eslint`    | Check/lint JavaScript (max-warnings=0)                  |
| `composer coverage`  | Run tests with code coverage (min 80%)                  |
| `npm run prettier`   | (Root level) Format all package code                    |
| `npm run eslint`     | (Root level) Lint all package JavaScript                |

## Code Standards & Conventions

### PHP

- **Version**: PHP 8.2 only — **no PHP 8.3+ features** (named arguments, `enum` with methods, readonly properties, etc.)
- **Formatting**: Laravel Pint (based on PSR-12)
- **Static Analysis**: PHPStan level 5+ (must pass `composer analyze`)
- **Refactoring**: Rector for consistency (check `composer rector`)
- **Naming**: No single-letter or cryptic variable names anywhere (closures, migrations, example prose)
- **Comments**: Minimal; only when the WHY is non-obvious (hidden constraints, workarounds, subtle invariants)

### JavaScript / Blade

- **Formatter**: Prettier with blade and Tailwind plugins
- **Linter**: ESLint with max-warnings=0 (no warnings allowed)
- **Tailwind**: CSS-in-Blade with utility classes (no custom CSS unless necessary)

### Architecture Rules (Strict)

Non-negotiable. These mirror the core repo — every package must follow them.

#### Laravel Actions (`lorisleiva/laravel-actions`)

- **All business logic lives in Actions.** Single-purpose invokable classes in each package's `src/Actions/`.
- **One public `handle()` per action.** Split by verb (`CreateBlogPost`, `PublishBlogPost`, `PublishContentWidget`).
- **Controllers, Filament resources/pages, Livewire components, and commands `::run()` actions** — they don't contain domain logic themselves.
- **Return typed values** from `handle()`, ideally a `Data` object.
- **Validation** on the action via `rules()` / `authorize()` when used `AsController`. No parallel FormRequest.
- **Test actions directly** — `MyAction::run(...)`, not through HTTP unless testing the HTTP surface.

#### Laravel Data (`spatie/laravel-data`)

Use extensively. Any structured value crossing a boundary is a `Data` object:

- **Inbound**: request payloads → `Data::from($request)`. No `$request->input(...)` in actions.
- **Outbound**: API responses, Filament form state, Livewire wire-props, Blade view models.
- **Model casts**: complex JSON/struct columns via `AsData::class` / `AsDataCollection::class`.
- **Widget/layout configuration**: package-specific widget configs hydrate from `Data` objects, not arrays.
- **Typed properties only** — no `mixed`, no untyped arrays. Use `DataCollection<T>` for lists.
- **Prefer `readonly` properties** on DTOs where state is immutable.
- **Don't wrap a single scalar in a `Data` class** — only use when it carries structure.

#### Actual conventions

- **Every PHP file starts with `declare(strict_types=1);`**
- **Actions**: `packages/{pkg}/src/Actions/`, naming `VerbNounAction` (extend `Lorisleiva\Actions\Action` or use `AsObject` trait). Match the core repo's patterns — see `capell-4/packages/core/src/Actions/` for the reference set (~90 examples).
- **Data**: `packages/{pkg}/src/Data/`, suffix `Data`, constructor-promoted public properties, getter methods for computed/closure-backed values.
- **Namespaces**: `Capell\Mosaic`, `Capell\Blog`, `Capell\Address`, `Capell\Assistant`.

#### Typical slice

1. Request → `SomeInputData`.
2. Component/Controller/Resource calls `SomeAction::run($inputData)`.
3. Action returns `SomeOutputData`.
4. Caller renders or serializes it.

Array literal with >2 keys crossing a layer? Make it a `Data` object.

### Package Structure

Each add-on package follows a consistent layout:

```
packages/package-name/
├── src/                     # Source code
├── database/
│   ├── migrations/          # Database migrations
│   └── seeders/             # Test data seeders
├── resources/
│   ├── views/               # Blade templates
│   └── js/                  # JavaScript
├── config/                  # Package config files
├── tests/                   # Unit & feature tests
├── phpstan/                 # PHPStan stubs
└── composer.json            # Package metadata
```

## Testing

### Framework: Pest

- **Configuration**: `phpunit.xml` in root
- **Test Location**: `packages/*/tests/` directories
- **Plugins**: Pest Laravel, Pest Livewire, Pest Arch, Pest Type Coverage
- **Coverage**: Minimum 80% required
- **Run Tests**: `composer test` (parallel execution)
- **Run with Coverage**: `composer coverage` or `composer coverage-report`

### Demo Workbench

- Located in `./demo/` directory
- Pre-configured Laravel application for testing packages
- Use `composer serve` to run locally
- Test all package functionality before committing

## Git & Commit Workflow

### Before Every Commit

1. **Run tests**: `composer test` — must pass 100%
2. **Run preflight**: `composer preflight` — fixes code style and checks static analysis
3. **Verify no short variable names** in your changes
4. **Test in demo workbench**: `composer serve` to verify package works end-to-end
5. **Commit message**: Clear, imperative tone ("Add X", "Fix Y", not "Fixed" or "Changes")

### Batching Strategy (Preferred)

For related changes across multiple packages:

- **Batch similar slices**: Group related changes into logical commits
- **Defer preflight**: Run tests frequently during development, but defer the full `composer preflight` suite until the end of a logical phase
- **Single PR**: Keep related work in one PR to avoid churn; splitting is acceptable only for unrelated features

### Branch Naming

- Feature: `feat/description` (e.g., `feat/layout-builder`)
- Bug: `fix/description` (e.g., `fix/widget-rendering`)
- Docs: `docs/description`
- Chore: `chore/description`

### Pull Requests

- **Target**: `4.x` branch
- **Description**: Summary of changes, which packages affected, why they matter, testing notes
- **CI**: GitHub Actions runs tests, code style checks on all PRs
- **Demo Tests**: Note any special setup needed in the demo workbench

## Project Structure

```
capell-packages-4/
├── .github/                      # GitHub Actions workflows
├── .ai/                          # AI context files
├── .claude/                      # Claude Code settings
│   └── settings.json             # Hooks, permissions, env vars
├── packages/                     # All add-on packages
│   ├── mosaic/                   # Example: Mosaic layout builder package
│   ├── blog/                     # Example: Blog package
│   └── [other-packages]/
├── demo/                         # Test workbench application
│   ├── app/                      # Laravel app config
│   ├── config/                   # Package service providers
│   └── resources/views/          # Demo views
├── tests/                        # Root-level integration tests
├── src/                          # Shared utilities (if any)
├── composer.json                 # Root composer config (monorepo)
├── composer.local.json           # Local overrides (git-ignored)
├── package.json                  # npm/node scripts
├── phpstan.neon                  # PHPStan config
├── phpunit.xml                   # Test configuration
├── rector.php                    # Rector config
├── pint.json                     # Pint config
├── .prettierrc                   # Prettier config
├── .eslintrc.json                # ESLint config
└── CONTRIBUTING.md               # Contribution guidelines
```

## Package Development Guidelines

### Creating a New Package

1. **Directory**: Create `packages/new-package-name/`
2. **Composer.json**: Define package metadata and dependencies
3. **Service Provider**: Create `ServiceProvider` in `src/` that registers package features
4. **Tests**: Add unit and feature tests in `tests/`
5. **Migrations** (if needed): Place in `database/migrations/`
6. **Resources**: Blade templates in `resources/views/`, JavaScript in `resources/js/`

### Package Dependencies

- All packages depend on `capell-app/core` at minimum
- Some may depend on `capell-app/admin` for Filament integration
- Minimize inter-package dependencies to keep packages loosely coupled

### Backward Compatibility

- Packages must maintain API compatibility with Capell 4.x
- Breaking changes require major version bumps
- New features should be additive, not modifying existing behavior

## Debugging Tips

### Testing a Package in the Demo Workbench

```bash
# 1. Start with fresh state
composer prepare

# 2. Run the demo application
composer serve

# 3. In another terminal, run tests for a specific package
php vendor/bin/pest packages/mosaic/tests

# 4. Check integration in the browser at http://localhost:8000
```

### Common Issues

**Package Not Loading**:

- Check `ServiceProvider` is registered in `composer.json` `extra.laravel.providers`
- Run `composer prepare` to rebuild service provider cache
- Verify package is listed in root `composer.json`

**Migration Errors**:

- Ensure migration files are in `packages/package-name/database/migrations/`
- Run `php artisan migrate --path=packages/package-name/database/migrations`

**Tests Failing**:

- Run `composer test` for full output
- Check that demo workbench has all required config
- Verify package is properly bootstrapped in test setup

**Static Analysis Failing**:

- Run `composer analyze` to see PHPStan errors
- Check `phpstan-ignore-errors.neon` for package-specific exceptions

## Packages in this repo

| Package     | Namespace          | Purpose                                        | Depends on                    |
| ----------- | ------------------ | ---------------------------------------------- | ----------------------------- |
| `mosaic`    | `Capell\Mosaic`    | Visual layout builder, widgets, content blocks | core, admin, frontend         |
| `blog`      | `Capell\Blog`      | Blog post types, categories, tags, RSS         | core, admin, frontend, mosaic |
| `address`   | `Capell\Address`   | Address/country management on Sites            | core, admin, frontend         |
| `assistant` | `Capell\Assistant` | AI-assisted editorial tooling                  | core, admin                   |

**Blog depends on Mosaic — install Mosaic first.** Keep inter-package dependencies minimal; most packages should only touch core + admin + frontend + their own layer.

## Extending Capell from a package

Every package plugs into core via the published facades — use these, don't bypass them:

- **Register types/schemas/widgets**: `CapellCore::registerPageType|registerSchema|registerWidget|overwriteType(...)` in the package's `ServiceProvider::register()`.
- **Auto-discovery**: types in `src/Types/`, schemas in `src/Schemas/`, widgets in `src/Widgets/` are picked up automatically.
- **Filament form hooks**: implement `PageSchemaExtender`, tag with `$this->app->tag([MyExtender::class], PageSchemaExtender::TAG)`. Hook enum positions: `BeforeTitle`, `AfterTitle`, `AfterContentEditor`, `AfterExtraContent`, `BeforeSearchMeta`, `AfterSearchMeta`.
- **Lifecycle events**: `CapellAdmin::register($event, $class, $callback)` or `CapellAdmin::subscribe($subscriber)`. Use `ValidationSubscriber` (returning false blocks) for delete/save guards.
- **Render hooks**: `RenderHookRegistry::register(RenderHookLocation::X, $callable, priority, scenario)` — inject HTML into Blade locations without overriding templates.
- **Settings**: `SettingsSchemaRegistry::register('pluginKey', MySchema::class)` + `registerSettingsClass('pluginKey', MySettings::class)`.

Refer to the core repo's [docs/extending-capell.md](../capell-4/docs/extending-capell.md) and [docs/settings-schema-registry.md](../capell-4/docs/settings-schema-registry.md) for the full surface.

## Workspaces / Draftable

- Any package model that participates in draft/publish must implement `Capell\Core\Contracts\Draftable` and be registered in the morph map in the package's service provider.
- Don't reinvent draft flags / replication — reuse `ReplicateModelAction`, `ReplicatePageAction`, etc. from `capell-app/admin`.

## Pest test layout

Tests mirror the core repo: grouped via `tests/Pest.php` by package, each group binds a package-specific TestCase. Test actions directly (`MyAction::run($input)`), not through HTTP. Arch tests enforce that packages don't reach into each other's internals.

## Relationship to Capell Core

This repository packages **optional, specialized functionality**. The core Capell CMS (`capell-app/capell`) provides:

- Page management
- Content types
- User authentication
- Admin framework

Capell Packages provide:

- Specialized editing experiences (layout builder, hero sections)
- Domain-specific content types (blog, address)
- Advanced features (design tools, integrations)

Packages should **not duplicate** core functionality. When a feature is broadly useful, it should be moved to core.

## Documentation

- **Core Docs**: See `capell-app/capell` repository docs
- **Package README**: Each package has its own README explaining:
    - What it does
    - Installation instructions
    - Configuration options
    - Usage examples
- **Contributing**: See CONTRIBUTING.md for pull request guidelines

## Tips for Claude Code Sessions

1. **Reach for Actions + Data first** — logic goes in an Action; structured values cross boundaries as `Data` objects, never arrays.
2. **Always run `composer test` before committing** — catches regressions early
3. **Test in the demo workbench** — functional testing complements unit tests
4. **Use `composer preflight` at phase boundaries** — not after every file change
5. **Batch similar changes** — avoid tiny commits that require repeated test runs
6. **Check variable names** — no single-letter vars, even in migrations
7. **PHP 8.2 only** — no readonly classes, no typed constants, no DNF types
8. **Respect package boundaries** — minimize dependencies between packages
9. **Use git worktrees** — `.claude/worktrees/` for isolating experimental branches
10. **Leverage path repos** — local package development via `composer.local.json`

## Useful Links

- **GitHub (Packages)**: https://github.com/capell-app/capell-packages
- **GitHub (Core)**: https://github.com/capell-app/capell
- **Package Registry**: https://packagist.org/packages/capell-app/
- **Laravel Docs**: https://laravel.com/docs
- **Filament Docs**: https://filamentphp.com
- **Pest Docs**: https://pestphp.com
