# Theme Studio Runtime Split Design

## Goal

Make `capell-app/theme-studio-admin` optional at runtime by moving Theme Studio runtime settings ownership into `capell-app/theme-studio-core`, while preserving current frontend rendering behavior and keeping the admin package as an optional editor and publishing layer.

## Context

Today the Theme Studio admin package owns three responsibilities that do not naturally belong together:

1. The Filament admin surface for browsing, previewing, staging, and publishing themes.
2. The persisted Theme Studio settings model and defaults.
3. The `ThemeRuntimeSettings` binding that frontend rendering depends on.

Because frontend rendering resolves `ThemeRuntimeSettings`, the admin package is currently required even in installs that do not need the Theme Studio Filament page. That makes the package boundary misleading: "admin" sounds optional, but runtime rendering depends on it.

## Desired Outcome

After this refactor:

- `capell-app/theme-studio-core` must be sufficient for runtime theme rendering.
- `capell-app/theme-studio-admin` must be optional.
- Removing the admin package must remove Theme Studio UI and publishing workflows, but must not break frontend runtime rendering.
- Existing installs with the admin package must behave the same from an editor perspective.
- Persisted settings storage must remain based on `Spatie\LaravelSettings`.

## Non-Goals

- No redesign of the Theme Studio page UX.
- No change to how active themes, presets, brand profile values, or overrides are interpreted.
- No new package extraction such as a separate `theme-studio-runtime` package.
- No migration away from `Spatie\LaravelSettings`.
- No unrelated refactor of theme registration, preview signing, or theme rendering internals.

## Recommended Approach

Move runtime settings ownership into `packages/theme-studio-core`, and leave `packages/theme-studio-admin` responsible only for optional admin-facing behavior.

This keeps the architecture simple:

- `core` owns what the frontend must have in every install.
- `admin` owns what editors need only when the Theme Studio UI is enabled.

This is preferable to adding fallback bindings in `core`, because fallback behavior would still leave runtime ownership conceptually split across packages. It is also preferable to creating a new runtime package, because the current scope does not justify the extra Composer and maintenance surface.

## Target Package Responsibilities

### Theme Studio Core

`packages/theme-studio-core` will own:

- `ThemeStudioSettings`
- the settings defaults migration
- registration of the settings class with the settings registry
- binding `ThemeRuntimeSettings` to `ThemeStudioSettings`
- any runtime-only tests that prove frontend rendering works without the admin package

Core will continue to own:

- theme registry
- preview context and signing
- runtime resolution actions
- token CSS generation hook
- page rendering actions

### Theme Studio Admin

`packages/theme-studio-admin` will own:

- `ThemeStudioPage`
- the editable Filament schema used to manage Theme Studio settings
- draft/publish workflows
- Workspaces approval handoff
- admin-focused tests covering page and publishing behavior

Admin will stop owning:

- the settings class definition
- the settings migration defaults
- the runtime binding used by frontend rendering

## Concrete Design

### 1. Move the settings class to core

`ThemeStudioSettings` should move from:

- `packages/theme-studio-admin/src/Settings/ThemeStudioSettings.php`

to:

- `packages/theme-studio-core/src/Settings/ThemeStudioSettings.php`

The class should continue to:

- implement `SettingsContract`
- implement `ThemeRuntimeSettings`
- expose `activeTheme()`
- expose `activePreset()`
- expose `brandProfile()`
- expose `themeOverrides()`

The property names and group name should remain unchanged so persisted data keys stay compatible:

- group: `theme_studio`
- keys such as `theme_studio.activeTheme`, `theme_studio.activePreset`, `theme_studio.brandProfile`, and `theme_studio.themeOverrides`

### 2. Keep the editable schema in admin

The Filament schema should remain in the admin package because it is an editor surface, not a runtime concern.

`ThemeStudioSettings::schema()` in core will still return the admin schema class when admin is installed. To make admin optional, core must not hard-depend on that class at boot time.

The cleanest way to achieve that is:

- core registers the settings class only
- admin registers the schema mapping for the `theme_studio` group when the admin package is installed

That means the settings object remains available everywhere, while the editable schema exists only when the UI package exists.

### 3. Move runtime registration into core

The following responsibilities move from `ThemeStudioAdminServiceProvider` into `ThemeStudioCoreServiceProvider`:

- binding `ThemeRuntimeSettings::class` to `ThemeStudioSettings::class`
- registering the settings class with `SettingsSchemaRegistry`

The schema registration stays in admin:

- `SettingsSchemaRegistry::register(ThemeStudioSettings::group(), ThemeStudioSettingsSchema::class)`

This split preserves editor configuration when admin is installed, while making frontend rendering independent of admin.

### 4. Move settings defaults migration into core

The settings migration currently published from admin should move into core so non-admin installs still receive the default values.

The migration contents should stay logically the same:

- active theme default
- active preset default
- draft theme and preset placeholders
- brand profile defaults
- theme overrides defaults

Keeping the draft-related keys is acceptable even when admin is not installed, because:

- they are harmless runtime state
- it avoids introducing a breaking settings shape change
- it keeps publish workflow compatibility for installs that later add the admin package

### 5. Update admin to consume core-owned settings

All admin actions, page logic, and publishing flows should switch their imports from the admin settings namespace to the core settings namespace.

This includes:

- stage draft action
- publish draft action
- activate approved draft action
- readiness checks
- page card rendering
- publishing strategy implementations

Behavior should remain unchanged; only ownership and imports move.

### 6. Keep package metadata honest

After the split:

- the core package should advertise that it provides Theme Studio runtime behavior
- the admin package should advertise that it provides the Theme Studio admin experience

Any package metadata that currently implies settings ownership in admin should be updated to reflect the new split.

## Data Compatibility

No persisted settings key names should change.

That means:

- no migration of stored keys is required
- no data backfill is required
- existing installations should continue reading the same values after the class moves to core

The refactor must be namespace-compatible at runtime and storage-compatible at the database/settings layer.

## Failure Modes To Avoid

### Frontend rendering fails without admin

This is the primary risk. If `ThemeRuntimeSettings` is still only bound by admin, frontend rendering and token CSS injection will fail or silently do nothing when admin is removed.

### Settings UI breaks with admin installed

If the schema registration no longer points at the moved settings class correctly, the Theme Studio page may render incomplete forms or fail to resolve settings values.

### Package boot order assumptions leak in

The implementation should not rely on fragile registration order between core and admin beyond standard Laravel service provider resolution. Core must be self-sufficient for runtime, and admin must layer on top cleanly.

### Published settings migration path becomes confusing

Moving the migration between packages risks duplicate or missing publish paths. The new location should be the single source of truth going forward.

## Testing Strategy

### Core tests

Add or update tests in `packages/theme-studio-core/tests` to prove:

- `ThemeRuntimeSettings` resolves when only core is loaded
- `RenderCurrentThemePageAction` still renders correctly without the admin provider
- token CSS render hook still works when core is installed without admin
- the moved settings class exposes the same defaults and data transformations

### Admin tests

Update tests in `packages/theme-studio-admin/tests` to prove:

- the Theme Studio page still resolves and renders
- the settings schema still targets the `theme_studio` settings group
- draft staging and publishing behavior is unchanged
- Workspaces handoff still activates approved drafts correctly

### Regression expectation

The most important regression test is explicit: core runtime rendering must pass without loading the admin provider.

## Implementation Notes

- Follow the existing `Actions + Data` architecture; do not move domain logic into service providers or page classes.
- Keep strict typing and existing naming conventions intact.
- Avoid introducing new abstractions unless they remove a real dependency edge.
- Keep the write set narrow: this is a package ownership refactor, not a product feature change.

## Open Decision Settled

For non-admin installs, runtime values will continue to use `Spatie\LaravelSettings`, not config-based fallbacks. That keeps persistence behavior consistent across installs and avoids dual sources of truth.

## Success Criteria

The refactor is successful when all of the following are true:

1. `capell-app/theme-studio-core` can render Theme Studio frontend output without `capell-app/theme-studio-admin` installed.
2. `capell-app/theme-studio-admin` can be removed without breaking runtime theme resolution.
3. Installs with admin still have the same Theme Studio page, staging flow, preview flow, and publish flow.
4. No persisted settings keys change.
5. Tests cover both the runtime-only path and the admin-enabled path.
