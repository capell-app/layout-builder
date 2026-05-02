# Testing

This monorepo uses [Pest](https://pestphp.com) 4.x with a parallel runner and pcov
for coverage. All packages are tested from the root.

## Quick start

```bash
composer install
composer test             # all packages, parallel
```

## Test commands

| Command                    | Purpose                          |
| -------------------------- | -------------------------------- |
| `composer test`            | Full suite (parallel)            |
| `composer test:unit`       | Unit suite only                  |
| `composer coverage`        | HTML coverage report (min 80%)   |
| `composer coverage-report` | Coverage summary in the terminal |

Run a single package:

```bash
php -d memory_limit=-1 vendor/bin/pest packages/theme-studio/core/tests
```

Run a single file:

```bash
php -d memory_limit=-1 vendor/bin/pest packages/theme-studio/core/tests/Unit
```

## Test suites

Tests are collected from these directory patterns (configured in `phpunit.xml`):

| Suite            | Directories                                                                              |
| ---------------- | ---------------------------------------------------------------------------------------- |
| **Unit**         | `tests/src/*/Unit`, `packages/*/*/tests/Unit`, `packages/*/themes/*/tests/Unit`          |
| **Feature**      | `tests/src/*/Feature`, `packages/*/*/tests/Feature`, `packages/*/themes/*/tests/Feature` |
| **Architecture** | `tests/src/*/Arch`                                                                       |
| **Integration**  | `tests/src/*/Integration`                                                                |

## Conventions

- **Framework**: Pest 4 function syntax (`test(...)`, `expect(...)`, `beforeAll(...)`)
- **No PHPUnit classes** — every test file uses top-level Pest functions only
- **Test actions directly**: `MyAction::run($input)`, not through HTTP unless testing an HTTP surface
- **Database tests**: use `Illuminate\Database\Capsule\Manager` with SQLite `:memory:` — no full
  Laravel app bootstrap required for unit tests
- **Mocking**: `Mockery::mock(InterfaceClass::class)` — no `$this->mock()` shorthand

## Coverage

Coverage is measured with pcov scoped to grouped package source directories:

```bash
composer coverage           # writes HTML to coverage-html/
composer coverage-report    # text summary only
```

The minimum threshold is **80%**. ServiceProviders, Console commands, and Middleware are excluded
from the measured source because they require an integration harness to test meaningfully.

## Pre-commit checks

The git hooks run these automatically before every commit:

1. Laravel Pint (code style)
2. Prettier (Blade / CSS / JS formatting)
3. ESLint

To run the full pre-flight suite manually:

```bash
composer preflight    # Prettier + ESLint + Rector + Pint + PHPStan
```

PHPStan runs at level 5. Annotate unavoidable suppressions with a comment explaining why.

## Adding tests for a new package

1. Create `packages/your-package/tests/Unit/` and place `*Test.php` files there.
2. The `phpunit.xml` source block includes grouped `packages/*/*/src` package paths automatically; no config changes needed.
3. Follow the existing structure: one `*Test.php` per class under test, named after the class.
4. Add a `beforeAll` or `beforeEach` hook in the test file for any shared setup.
