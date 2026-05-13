---
applyTo: '**/*.php'
---

# PHP and Laravel Packages

- Add `declare(strict_types=1);` to every PHP source file.
- Use explicit parameter and return types on methods and closures. Use precise PHPDoc only for generics, array shapes, or external contracts PHP cannot express.
- Avoid one-letter variables in production code, tests, closures, migrations, and examples.
- Prefer full closures over arrow functions when a parameter or return type improves clarity.
- Use Actions for business operations and Data objects for structured boundaries.
- Use backed enums for persisted values and shared string contracts. Implement Filament labels when used in admin options.
- Keep comments sparse. Explain rationale, migration, security, performance, or non-obvious invariants only.
- Do not add test-only branches such as `app()->runningUnitTests()` to production code.
