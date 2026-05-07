# Capell Frontend Optimizer

Profile-based CSS and JavaScript delivery for public Capell pages.

The package keeps frontend optimization out of themes. Themes, layouts, widgets,
and blocks declare the assets they need; the optimizer resolves a render profile,
stores a manifest, queues Playwright critical CSS generation, and renders only the
assets for that profile.

## Runtime Model

1. Register layout and widget asset sets from PHP package code.
2. Resolve the optimization scope. Layout overrides win, then site overrides, then
   `config('capell-frontend-optimizer.scope')`.
3. Prepare a render profile with `PrepareRenderProfileAction`.
4. Render the profile assets in the public layout with `@frontendOptimizerAssets($profileHash)`.
5. If critical CSS is missing or failed, normal CSS and JavaScript still render.
   The queued job fails loudly, but public pages do not break.

## Registering Assets

```php
use Capell\FrontendOptimizer\Enums\AssetLoadingStrategy;
use Capell\FrontendOptimizer\Enums\AssetSlot;
use Capell\FrontendOptimizer\Support\FrontendAssetSet;
use Capell\FrontendOptimizer\Support\LayoutAssetRegistry;
use Capell\FrontendOptimizer\Support\WidgetAssetRegistry;

app(LayoutAssetRegistry::class)->register(
    'landing',
    FrontendAssetSet::make()
        ->css('theme-base', '/build/theme/base.css', AssetLoadingStrategy::Blocking, AssetSlot::Base)
        ->css('hero', '/build/theme/hero.css', AssetLoadingStrategy::Critical, AssetSlot::AboveFold, criticalEligible: true),
);

app(WidgetAssetRegistry::class)->register(
    'carousel',
    FrontendAssetSet::make()
        ->css('carousel', '/build/widgets/carousel.css', AssetLoadingStrategy::Preload, AssetSlot::AboveFold, criticalEligible: true)
        ->js('carousel', '/build/widgets/carousel.js', AssetLoadingStrategy::Deferred),
);
```

Widget registrations can include a condition closure when an asset only applies
to some widget instances.

```php
app(WidgetAssetRegistry::class)->register(
    'asset-list',
    FrontendAssetSet::make()->js('carousel', '/build/widgets/carousel.js'),
    static fn (array $widgetData): bool => ($widgetData['display'] ?? null) === 'carousel',
);
```

## Preparing A Profile

```php
use Capell\FrontendOptimizer\Actions\PrepareRenderProfileAction;
use Capell\FrontendOptimizer\Actions\ResolveOptimizationScopeAction;

$scope = ResolveOptimizationScopeAction::run(
    layoutScope: $layout->frontend_optimization_scope,
    siteScope: $site->frontend_optimization_scope,
);

$profile = PrepareRenderProfileAction::run(
    scope: $scope,
    context: [
        'layout' => $layout->getKey(),
        'theme' => $theme->getKey(),
        'widgets' => $widgetSignature,
    ],
    assetSets: $assetSets,
    url: request()->fullUrl(),
    label: $layout->name,
);
```

Render the assets in the page layout:

```blade
@frontendOptimizerAssets($profile->hash)
```

The directive emits plain public HTML only. It does not expose editor metadata,
model identifiers, signed URLs, or Capell package markers.

## Critical CSS

Critical CSS generation requires Node and Playwright. There is no Beasties or
Critters fallback.

```bash
npm install
npm run playwright:install
```

The worker opens the page in Chromium for each configured viewport and collects
CSS rules that match visible above-the-fold elements. Stylesheet inspection is
limited to profile CSS assets that are critical eligible, above fold, base, head,
blocking, or preload assets. Below-fold lazy assets are ignored.

If generation fails, `GenerateCriticalCssJob` fails and records the run. Public
rendering continues with normal stylesheet and script tags.

## Configuration

```php
'enabled' => true,
'scope' => 'layout',
'paths' => [
    'manifests' => 'capell/frontend-optimizer/manifests',
    'critical_css' => 'capell/frontend-optimizer/critical-css',
],
'playwright' => [
    'node_binary' => env('CAPELL_FRONTEND_OPTIMIZER_NODE', 'node'),
    'timeout' => 120,
    'viewports' => [
        ['width' => 390, 'height' => 844],
        ['width' => 1440, 'height' => 900],
    ],
],
```

Keep theme-level manual critical CSS fields as legacy input only. New theme and
widget packages should register assets with this optimizer instead of baking
critical CSS paths into theme configuration.
