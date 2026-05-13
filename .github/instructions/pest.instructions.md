---
applyTo: '**/*Test.php,tests/**/*.php,packages/*/tests/**/*.php'
---

# Pest Tests

- Use Pest, not new PHPUnit test classes.
- Do not use `php artisan test` in this repository.
- Test Actions through `ActionClass::run()` unless the test is specifically about an alternate entrypoint.
- Start narrow: `vendor/bin/pest packages/{package}/tests --configuration=phpunit.xml --filter=<name>`.
- For package integration, use real package providers, config, migrations, and models where practical. Mock only external services.
- Add expected behavior plus fallback/denied/edge-path assertions for package APIs, public rendering, cache behavior, migrations, and admin workflows.
