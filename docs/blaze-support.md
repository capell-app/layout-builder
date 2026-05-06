# Blaze Support

Capell packages register anonymous Blade component directories with Livewire Blaze using function compilation only.

## Default Strategy

- `compile: true`
- `memo: false`
- `fold: false`

## Advanced Strategy Rules

Memoization may be enabled only for components with no slots.

Folding may be enabled only after checking the component does not read global state, request/session/auth data, validation errors, shared view data, render hooks, Blade stacks, or CSRF tokens.

## Current Advanced Strategy Exclusions

- `packages/frontend-authoring/resources/views/authoring/bootstrap-script.blade.php` is returned from the admin-only beacon response, not compiled as a public anonymous component.
- `packages/publishing-studio/resources/views/components/workspace-preview-pill.blade.php` reads the current request URL.
- `packages/foundation-theme/resources/views/components/header/index.blade.php` uses `@push`.
- `packages/layout-builder/resources/views/components/hero/content.blade.php` and `packages/layout-builder/resources/views/components/widget/wrapper.blade.php` use `@aware`, so parent and child Blaze coverage must stay aligned.

## Rollout

In a consuming Laravel app, run `php artisan view:clear` after changing Blaze registrations. In this monorepo, run `composer clear:views`.
Set `BLAZE_ENABLED=false` to compare against Blade rendering.
Set `BLAZE_DEBUG=true` to use Blaze's debug overlay and profiler.
Set `CAPELL_BLAZE_THROW=true` in local development when auditing fold candidates.
