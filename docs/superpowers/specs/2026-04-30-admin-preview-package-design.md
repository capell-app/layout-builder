# Admin Preview Package Design

## Goal

Create an optional `capell-app/admin-preview` package that integrates Capell admin previews with `pboivin/admin-preview`, with first-class support for PublishingStudio draft previews loaded inside an iframe modal.

## Context

Capell PublishingStudio already owns draft preview URL generation through `Capell\PublishingStudio\Actions\GenerateWorkspacePreviewUrlAction`. That action creates a temporary signed frontend URL carrying the workspace UUID and preview-link token, and `ResolveWorkspaceContext` resolves those values into draft context for the rendered website.

The root monorepo currently requires `pboivin/admin-preview`, the shared test harness registers `AdminPreviewServiceProvider`, and `capell-app/admin` registers `AdminPreviewPlugin` directly in its panel provider. The existing PublishingStudio preview action opens the generated frontend URL in a new tab.

The new package should move the Peek dependency and integration out of the global dependency surface. Installing it should enhance the PublishingStudio admin table with an iframe modal preview while keeping the existing PublishingStudio package independent and usable without Peek.

## Package Shape

The package lives at `packages/admin-preview`.

- Composer package: `capell-app/admin-preview`
- Namespace: `Capell\AdminPreview`
- Translation namespace: `capell-admin-preview`
- Service provider: `Capell\AdminPreview\Providers\AdminPreviewServiceProvider`

The package requires:

- `php:^8.2`
- `capell-app/admin:*`
- `capell-app/frontend:*`
- `capell-app/publishing-studio:*`
- `pboivin/admin-preview:^4.1`

The package should be optional. No existing package should require it unless that package explicitly wants the modal preview integration.

## Architecture

PublishingStudio remains the source of truth for draft preview URLs. The new package contributes only admin UI integration around those URLs.

The integration has two pieces:

1. A package service provider registers `capell-app/admin-preview` with `CapellCore`, loads package translations, and tags an admin panel extender.
2. The admin panel extender registers `Pboivin\AdminPreview\AdminPreviewPlugin` through the existing `AdminPanelExtender` extension point.
3. A PublishingStudio table action opens the Admin Preview modal whose iframe URL comes from `GenerateWorkspacePreviewUrlAction`.

PublishingStudio should expose a minimal action extension point if the current table cannot be extended cleanly. The preferred shape is a tagged contributor contract that can return additional record actions for the PublishingStudio table. The Admin Preview package tags its contributor when both frontend and publishing-studio are installed.

This keeps PublishingStudio independent from `Pboivin\AdminPreview\*` classes and prevents the root PublishingStudio test case from needing the Peek service provider. It also means `capell-app/admin` must stop requiring and registering Admin Preview directly. After the split, admin should rely on the existing `AdminPanelExtender` tag for optional panel plugins.

## Workspace Draft Preview Behaviour

The modal preview action should:

- appear only when `capell-app/frontend`, `capell-app/publishing-studio`, and `capell-app/admin-preview` are installed
- authorize with the same `view` ability as the existing PublishingStudio preview action
- generate the iframe URL through `GenerateWorkspacePreviewUrlAction`
- preserve the existing workspace preview query parameters and preview-link token
- use the full-screen Peek modal so editors can resize the iframe using Peek's built-in presets

The existing PublishingStudio new-tab preview action should remain available as the baseline preview. The modal action can have a distinct label such as `Preview in modal`, or replace the table action only through the extension point if the table design would otherwise become noisy. The first implementation should prefer an additive modal action because it is safer and preserves current behaviour.

## Extension Point

Add a small contract inside PublishingStudio:

```php
namespace Capell\PublishingStudio\Contracts;

interface WorkspaceTableActionContributor
{
    public const TAG = 'capell.publishing-studio.table-action-contributor';

    public function actions(): array;
}
```

`PublishingStudioTable` should resolve all tagged contributors and append their actions after the existing `PreviewAction`, before validation and comparison actions. This allows optional packages to enhance the PublishingStudio resource without PublishingStudio importing plugin classes.

The contract returns an array because Filament table actions are object instances, and optional packages may contribute more than one action later.

## Admin Package Companion Change

The companion admin package currently imports `Pboivin\AdminPreview\AdminPreviewPlugin` in `Capell\Admin\Providers\Filament\AdminPanelProvider` and requires `pboivin/admin-preview` in `packages/admin/composer.json`.

That direct dependency should be removed in the admin package. `AdminPanelProvider` should only register `CapellAdminPlugin`; `CapellAdminPlugin` already applies tagged `AdminPanelExtender` instances, which gives this optional package a clean way to register Peek when installed.

## Admin Preview Action

The new package should provide a focused action class, for example:

```php
Capell\AdminPreview\Filament\Resources\PublishingStudio\Actions\WorkspacePeekPreviewAction
```

The action should extend `Filament\Actions\Action` and use upstream Peek services to render and open the modal:

- call `Pboivin\AdminPreview\Facades\Peek::ensurePluginIsLoaded()` in the action callback
- call `Pboivin\AdminPreview\Facades\Peek::registerPreviewModal()` during setup
- dispatch the same `open-preview-modal` browser event used by Peek's own `HasPreviewModal` trait
- pass `iframeUrl` as the URL returned by `GenerateWorkspacePreviewUrlAction`
- pass `iframeContent` as `null`

This avoids adding `Pboivin\AdminPreview\Pages\Concerns\HasPreviewModal` to PublishingStudio page classes while still using Peek's plugin, assets, modal markup, and JavaScript event contract.

## Translations

All user-facing strings live under `packages/admin-preview/resources/lang/en`.

Suggested keys:

- `workspace.actions.preview_modal`
- `workspace.actions.preview_modal_tooltip`

The action should use `__('capell-admin-preview::workspace.actions.preview_modal')` for labels.

## Testing

PublishingStudio tests should cover the extension point without loading `pboivin/admin-preview`.

Admin Preview package tests should cover:

- the package provider registers `capell-app/admin-preview`
- the contributor implements the PublishingStudio table action contributor contract
- the contributor returns the modal preview action only when required packages are installed
- the modal action generates a signed workspace draft preview URL through `GenerateWorkspacePreviewUrlAction`
- the generated URL contains the workspace query parameter and preview link token

Run focused tests first:

```bash
vendor/bin/pest packages/publishing-studio/tests packages/admin-preview/tests --no-coverage
```

Then run the full suite or at least `composer preflight` before committing the implementation.

## Out Of Scope

- Building a custom iframe modal outside Admin Preview
- Reworking workspace preview token semantics
- Adding preview support for every admin resource in the first pass
- Adding a generic admin preview registry before a second concrete use case exists
- Changing frontend rendering or draft middleware behaviour

## Verified Peek API Notes

Peek `4.1.2` provides `Pboivin\AdminPreview\Tables\Actions\ListPreviewAction`, but that action requires the Livewire page to use `Pboivin\AdminPreview\Pages\Concerns\HasPreviewModal`. PublishingStudio should not import that trait because Peek is optional. The package action should therefore open the Peek modal by dispatching Peek's documented modal event directly after ensuring the Peek plugin is loaded.
