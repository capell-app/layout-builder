# Filament Peek Package Design

## Goal

Create an optional `capell-app/filament-peek` package that integrates Capell admin previews with `pboivin/filament-peek`, with first-class support for Workspaces draft previews loaded inside an iframe modal.

## Context

Capell Workspaces already owns draft preview URL generation through `Capell\Workspaces\Actions\GenerateWorkspacePreviewUrlAction`. That action creates a temporary signed frontend URL carrying the workspace UUID and preview-link token, and `ResolveWorkspaceContext` resolves those values into draft context for the rendered website.

The root monorepo currently requires `pboivin/filament-peek`, the shared test harness registers `FilamentPeekServiceProvider`, and `capell-app/admin` registers `FilamentPeekPlugin` directly in its panel provider. The existing Workspaces preview action opens the generated frontend URL in a new tab.

The new package should move the Peek dependency and integration out of the global dependency surface. Installing it should enhance the Workspaces admin table with an iframe modal preview while keeping the existing Workspaces package independent and usable without Peek.

## Package Shape

The package lives at `packages/filament-peek`.

- Composer package: `capell-app/filament-peek`
- Namespace: `Capell\FilamentPeek`
- Translation namespace: `capell-filament-peek`
- Service provider: `Capell\FilamentPeek\Providers\FilamentPeekServiceProvider`

The package requires:

- `php:^8.2`
- `capell-app/admin:*`
- `capell-app/frontend:*`
- `capell-app/workspaces:*`
- `pboivin/filament-peek:^4.1`

The package should be optional. No existing package should require it unless that package explicitly wants the modal preview integration.

## Architecture

Workspaces remains the source of truth for draft preview URLs. The new package contributes only admin UI integration around those URLs.

The integration has two pieces:

1. A package service provider registers `capell-app/filament-peek` with `CapellCore`, loads package translations, and tags an admin panel extender.
2. The admin panel extender registers `Pboivin\FilamentPeek\FilamentPeekPlugin` through the existing `AdminPanelExtender` extension point.
3. A Workspaces table action opens the Filament Peek modal whose iframe URL comes from `GenerateWorkspacePreviewUrlAction`.

Workspaces should expose a minimal action extension point if the current table cannot be extended cleanly. The preferred shape is a tagged contributor contract that can return additional record actions for the Workspaces table. The Filament Peek package tags its contributor when both frontend and workspaces are installed.

This keeps Workspaces independent from `Pboivin\FilamentPeek\*` classes and prevents the root Workspaces test case from needing the Peek service provider. It also means `capell-app/admin` must stop requiring and registering Filament Peek directly. After the split, admin should rely on the existing `AdminPanelExtender` tag for optional panel plugins.

## Workspace Draft Preview Behaviour

The modal preview action should:

- appear only when `capell-app/frontend`, `capell-app/workspaces`, and `capell-app/filament-peek` are installed
- authorize with the same `view` ability as the existing Workspaces preview action
- generate the iframe URL through `GenerateWorkspacePreviewUrlAction`
- preserve the existing workspace preview query parameters and preview-link token
- use the full-screen Peek modal so editors can resize the iframe using Peek's built-in presets

The existing Workspaces new-tab preview action should remain available as the baseline preview. The modal action can have a distinct label such as `Preview in modal`, or replace the table action only through the extension point if the table design would otherwise become noisy. The first implementation should prefer an additive modal action because it is safer and preserves current behaviour.

## Extension Point

Add a small contract inside Workspaces:

```php
namespace Capell\Workspaces\Contracts;

interface WorkspaceTableActionContributor
{
    public const TAG = 'capell.workspaces.table-action-contributor';

    public function actions(): array;
}
```

`WorkspacesTable` should resolve all tagged contributors and append their actions after the existing `PreviewAction`, before validation and comparison actions. This allows optional packages to enhance the Workspaces resource without Workspaces importing plugin classes.

The contract returns an array because Filament table actions are object instances, and optional packages may contribute more than one action later.

## Admin Package Companion Change

The companion admin package currently imports `Pboivin\FilamentPeek\FilamentPeekPlugin` in `Capell\Admin\Providers\Filament\AdminPanelProvider` and requires `pboivin/filament-peek` in `packages/admin/composer.json`.

That direct dependency should be removed in the admin package. `AdminPanelProvider` should only register `CapellAdminPlugin`; `CapellAdminPlugin` already applies tagged `AdminPanelExtender` instances, which gives this optional package a clean way to register Peek when installed.

## Filament Peek Action

The new package should provide a focused action class, for example:

```php
Capell\FilamentPeek\Filament\Resources\Workspaces\Actions\WorkspacePeekPreviewAction
```

The action should extend `Filament\Actions\Action` and use upstream Peek services to render and open the modal:

- call `Pboivin\FilamentPeek\Facades\Peek::ensurePluginIsLoaded()` in the action callback
- call `Pboivin\FilamentPeek\Facades\Peek::registerPreviewModal()` during setup
- dispatch the same `open-preview-modal` browser event used by Peek's own `HasPreviewModal` trait
- pass `iframeUrl` as the URL returned by `GenerateWorkspacePreviewUrlAction`
- pass `iframeContent` as `null`

This avoids adding `Pboivin\FilamentPeek\Pages\Concerns\HasPreviewModal` to Workspaces page classes while still using Peek's plugin, assets, modal markup, and JavaScript event contract.

## Translations

All user-facing strings live under `packages/filament-peek/resources/lang/en`.

Suggested keys:

- `workspace.actions.preview_modal`
- `workspace.actions.preview_modal_tooltip`

The action should use `__('capell-filament-peek::workspace.actions.preview_modal')` for labels.

## Testing

Workspaces tests should cover the extension point without loading `pboivin/filament-peek`.

Filament Peek package tests should cover:

- the package provider registers `capell-app/filament-peek`
- the contributor implements the Workspaces table action contributor contract
- the contributor returns the modal preview action only when required packages are installed
- the modal action generates a signed workspace draft preview URL through `GenerateWorkspacePreviewUrlAction`
- the generated URL contains the workspace query parameter and preview link token

Run focused tests first:

```bash
vendor/bin/pest packages/workspaces/tests packages/filament-peek/tests --no-coverage
```

Then run the full suite or at least `composer preflight` before committing the implementation.

## Out Of Scope

- Building a custom iframe modal outside Filament Peek
- Reworking workspace preview token semantics
- Adding preview support for every admin resource in the first pass
- Adding a generic admin preview registry before a second concrete use case exists
- Changing frontend rendering or draft middleware behaviour

## Verified Peek API Notes

Peek `4.1.2` provides `Pboivin\FilamentPeek\Tables\Actions\ListPreviewAction`, but that action requires the Livewire page to use `Pboivin\FilamentPeek\Pages\Concerns\HasPreviewModal`. Workspaces should not import that trait because Peek is optional. The package action should therefore open the Peek modal by dispatching Peek's documented modal event directly after ensuring the Peek plugin is loaded.
