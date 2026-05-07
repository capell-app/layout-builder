# Creating A Capell Theme

Capell themes are normal Composer packages. There is no separate Theme Studio install step. A site chooses one active theme key, and that theme can extend one base theme, usually `capell-app/foundation-theme`.

The runtime layers theme data in this order:

1. Parent package preset defaults.
2. Child package preset defaults.
3. Database edits from the Theme admin page.

Database edits always win. View fallback follows the same parent chain through the normal frontend theme view registrar, so a child theme only needs to ship the views it wants to change.

## 1. Choose The Theme Shape

Use Foundation Theme directly when the site only needs the default look:

```json
{
    "name": "capell-app/foundation-theme",
    "kind": "theme",
    "themeKey": "default"
}
```

Create a child theme when you want the Foundation rendering surface with a new visual treatment:

```json
{
    "name": "vendor/theme-client",
    "kind": "theme",
    "themeKey": "client",
    "extends": "capell-app/foundation-theme",
    "requires": ["capell-app/foundation-theme"]
}
```

Create a fully separate theme only when you need a different base contract. It should still declare `kind: "theme"` and a stable `themeKey`.

## 2. Add Package Metadata

Add `capell.json` beside the package `composer.json`.

```json
{
    "name": "vendor/theme-client",
    "kind": "theme",
    "capell-version": "^4.0",
    "productGroup": "Capell Themes",
    "tier": "premium",
    "bundle": "themes",
    "themeKey": "client",
    "contexts": ["frontend"],
    "extends": "capell-app/foundation-theme",
    "requires": ["capell-app/foundation-theme"],
    "providers": {
        "shared": ["Vendor\\ClientTheme\\ClientThemeServiceProvider"]
    }
}
```

`themeKey` is the key stored on the `themes` table and selected during install. If it is missing, Capell derives a key from the package name, but explicit keys are clearer and safer.

## 3. Register The Package

In the service provider, register the package as a theme. Keep the provider small: package registration in `register()`, runtime registration in `boot()`.

```php
<?php

declare(strict_types=1);

namespace Vendor\ClientTheme;

use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;
use Illuminate\Support\ServiceProvider;

final class ClientThemeServiceProvider extends ServiceProvider
{
    public const THEME_KEY = 'client';

    public static string $packageName = 'vendor/theme-client';

    public function register(): void
    {
        CapellCore::registerPackage(
            name: self::$packageName,
            type: PackageTypeEnum::Theme,
            path: realpath(__DIR__ . '/..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
        );
    }

    public function boot(ThemeRegistry $registry): void
    {
        if (! CapellCore::isPackageInstalled(self::$packageName)) {
            return;
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'vendor-theme-client');

        // Register the theme definition, page renderer, and section renderers here.
    }
}
```

Premium themes such as Agency, Corporate, and SaaS follow this pattern. They do not depend on a Studio metapackage.

## 4. Define Presets

Presets are package defaults. They should describe the starting point, not every possible edit an admin can make.

```php
new ThemePresetData(
    key: 'launch',
    name: 'Launch',
    description: 'Crisp product framing with compact proof and clear calls to action.',
    previewImage: '/vendor/client-theme/launch.jpg',
    values: [
        'primaryColor' => '#2563eb',
        'accentColor' => '#14b8a6',
        'headingFont' => 'inter',
        'cardStyle' => 'bordered',
        'layoutPresentation' => 'structured',
    ],
)
```

When a child theme extends Foundation, Foundation defaults are applied first, then the child preset fills or replaces values. The Theme admin page stores final edits in the database and those values override both package layers.

## 5. Register Renderers And Views

Use shared section keys where possible:

- `navigation`
- `hero`
- `features`
- `proof`
- `content-listing`
- `cta`
- `footer`

Point the page renderer at the shared `capell::page` layout when the theme extends Foundation. Ship package views only for the sections you want to control.

```php
new BladeThemeRenderer(
    themeKey: self::THEME_KEY,
    layoutView: 'capell::page',
    sectionRenderers: $sectionRenderers,
)
```

If a section has no child view, fallback continues through the parent theme chain. App-level overrides still win before package views.

## 6. Install And Select The Theme

The CLI and web installer both understand theme selection:

```bash
php artisan capell:install --packages=vendor/theme-client --theme=client
```

The installer only asks for a theme when there is a real choice:

- more than one selected or installed theme candidate exists
- a selected non-default theme package needs an explicit active theme

Marketplace installs use the same package metadata. When Deployments is installed, Marketplace publishes the Composer change through the deployment publisher. Without Deployments, it shows the Composer command so the change can be applied manually.

## 7. Test The Theme

At minimum, add tests for:

- `capell.json` declares `kind: "theme"`, a stable `themeKey`, and the correct `extends` package.
- The service provider registers the theme only when the package is installed.
- The theme definition includes its presets, sections, package name, and assets.
- Section renderers render the expected package views.
- Child defaults layer over parent defaults, and database edits win.
- Anonymous frontend output does not expose editor or package-internal metadata.

Run the package tests directly:

```bash
vendor/bin/pest packages/theme-client/tests --configuration=phpunit.xml
```

For installer or marketplace changes, run the matching host-app tests in `../capell-4`.
