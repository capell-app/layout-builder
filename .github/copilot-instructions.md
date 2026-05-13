# Copilot Instructions for Capell Packages

Keep this always-on context short. Load `AGENTS.md` for deeper repository policy only when the task needs it.

- This repo contains optional Capell companion packages under `packages/`. Core lives in `../capell-4`.
- Nearly all new Capell packages should be added here unless the request says otherwise.
- Start from the package, test, class, or command named in the request. Avoid scanning unrelated packages.
- Treat `composer.json`, `composer.local.json`, and sibling package providers as source of truth for namespaces, package discovery, and local overlays.
- Use strict typed PHP, PSR-12, descriptive names, explicit method and closure types, and `declare(strict_types=1);`.
- Keep package behavior in Actions/Data/services. Keep Filament, Livewire, commands, views, and app integration thin.
- Do not run `php artisan` in this repo. Use `vendor/bin/pest`, Composer scripts, and package-local tooling.
- User-facing strings go through package translations.
- Do not edit generated screenshots, caches, `vendor`, `node_modules`, storage, or build output unless explicitly requested.

Path-specific rules live in `.github/instructions/*.instructions.md`; check response references to confirm Copilot loaded the relevant files.
