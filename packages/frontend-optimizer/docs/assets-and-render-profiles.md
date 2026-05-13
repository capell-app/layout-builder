# Assets And Render Profiles

Frontend Optimizer turns package asset registrations into render profiles. A render profile is a stable hash built from the asset list, optimization scope, and context.

Use this package for public frontend CSS and JavaScript delivery. Do not use it for admin assets.

## Main Surfaces

| Surface                                  | Purpose                                                               |
| ---------------------------------------- | --------------------------------------------------------------------- |
| `LayoutAssetRegistry`                    | Assets that belong to a layout key.                                   |
| `WidgetAssetRegistry`                    | Assets that belong to a widget type, optionally gated by widget data. |
| `FrontendAssetSet`                       | Fluent builder for CSS and JavaScript asset definitions.              |
| `ResolveRenderProfileAction`             | Merges asset sets and creates the profile hash/signature.             |
| `StoreRenderProfileManifestAction`       | Persists the generated manifest.                                      |
| `GenerateCriticalCssAction`              | Runs the configured `CriticalCssGenerator`.                           |
| `@frontendOptimizerAssets($profileHash)` | Blade directive that renders stored assets for a profile.             |

## Register Layout Assets

```php
use Capell\FrontendOptimizer\Enums\AssetLoadingStrategy;
use Capell\FrontendOptimizer\Enums\AssetSlot;
use Capell\FrontendOptimizer\Support\FrontendAssetSet;
use Capell\FrontendOptimizer\Support\LayoutAssetRegistry;

$this->app->afterResolving(LayoutAssetRegistry::class, static function (LayoutAssetRegistry $registry): void {
    $registry->register(
        'marketing-page',
        FrontendAssetSet::make()
            ->css(
                handle: 'marketing-layout',
                path: 'vendor/capell/marketing/layout.css',
                loadingStrategy: AssetLoadingStrategy::Critical,
                slot: AssetSlot::Base,
                criticalEligible: true,
                packageName: 'capell-app/marketing',
            )
            ->js(
                handle: 'marketing-layout',
                path: 'vendor/capell/marketing/layout.js',
                loadingStrategy: AssetLoadingStrategy::Deferred,
                slot: AssetSlot::Interactive,
                packageName: 'capell-app/marketing',
            ),
    );
});
```

Handles and paths cannot be empty. JavaScript cannot use the `Critical` loading strategy.

## Register Widget Assets

Widget assets can be conditional. The condition receives widget data and must return `true` to include the asset set.

```php
use Capell\FrontendOptimizer\Support\FrontendAssetSet;
use Capell\FrontendOptimizer\Support\WidgetAssetRegistry;

$this->app->afterResolving(WidgetAssetRegistry::class, static function (WidgetAssetRegistry $registry): void {
    $registry->register(
        'video-embed',
        FrontendAssetSet::make()
            ->js(
                handle: 'video-embed',
                path: 'vendor/capell/video/embed.js',
                packageName: 'capell-app/video',
            ),
        static fn (array $widgetData): bool => ($widgetData['provider'] ?? null) === 'vimeo',
    );
});
```

Keep the condition pure. It may run while resolving a public page render profile.

## Build and Render a Profile

```php
use Capell\FrontendOptimizer\Actions\ResolveRenderProfileAction;
use Capell\FrontendOptimizer\Enums\OptimizationScope;
use Capell\FrontendOptimizer\Support\FrontendAssetSet;

$profile = ResolveRenderProfileAction::run(
    scope: OptimizationScope::Layout,
    context: [
        'layout' => 'marketing-page',
        'language' => 'en',
    ],
    assetSets: [
        FrontendAssetSet::make()->css('marketing-layout', 'vendor/capell/marketing/layout.css'),
    ],
    label: 'Marketing page',
);
```

Render stored assets from Blade after the manifest has been persisted:

```blade
@frontendOptimizerAssets($profileHash)
```

## Config Keys

| Key                                                | Use                                           |
| -------------------------------------------------- | --------------------------------------------- |
| `capell-frontend-optimizer.enabled`                | Enables optimization behavior.                |
| `capell-frontend-optimizer.scope`                  | Default optimization scope.                   |
| `capell-frontend-optimizer.paths.manifests`        | Storage path for render manifests.            |
| `capell-frontend-optimizer.paths.critical_css`     | Storage path for generated critical CSS.      |
| `capell-frontend-optimizer.playwright.node_binary` | Node binary used by the Playwright generator. |
| `capell-frontend-optimizer.playwright.script`      | Critical CSS script path.                     |
| `capell-frontend-optimizer.playwright.timeout`     | Generator timeout in seconds.                 |
| `capell-frontend-optimizer.playwright.viewports`   | Viewports used for critical CSS extraction.   |

`CAPELL_FRONTEND_OPTIMIZER_NODE` overrides the Node binary in local or deployed environments.

## Verification

```bash
vendor/bin/pest packages/frontend-optimizer/tests --configuration=phpunit.xml
```
