# Rendering And Sync

Navigation owns editable navigation trees, render models, header render hooks, and page cleanup when URLs or replicated sites change.

## Main Surfaces

| Surface                                  | Use                                                      |
| ---------------------------------------- | -------------------------------------------------------- |
| `BuildNavigationRenderModelAction`       | Builds the frontend render model.                        |
| `NavigationNamesResolver`                | Resolves navigation names for a site and languages.      |
| `NavigationPageSyncer`                   | Removes deleted or detached pages from navigation items. |
| `RegisterFoundationHeaderNavigationHook` | Adds the foundation header navigation render hook.       |
| `NavigationObserver`                     | Clears frontend navigation cache keys after changes.     |
| `ReplicateSiteNavigationsListener`       | Copies navigation rows when a site is replicated.        |

## Render a Navigation

```php
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Models\Navigation;

/** @var Navigation $navigation */
$renderModel = BuildNavigationRenderModelAction::run(
    new NavigationRenderContextData(
        navigation: $navigation,
        page: $page,
        site: $site,
        language: $language,
        siteDomain: $siteDomain,
    ),
);
```

Use the action from Blade view models or frontend components. Do not read the JSON column directly in templates.

## Replace Name Resolution

`NavigationNamesResolver` lets host apps control the navigation names available for a site/language pair.

```php
use Capell\Navigation\Contracts\NavigationNamesResolver;

final class DemoNavigationNamesResolver implements NavigationNamesResolver
{
    public function resolve(?int $siteId, array $languageIds): array
    {
        return [
            1 => 'Header',
            2 => 'Footer',
        ];
    }
}

$this->app->singleton(NavigationNamesResolver::class, DemoNavigationNamesResolver::class);
```

Return an array keyed by language ID. Keep names short because they appear in admin selects.

## Sync Pages Out of Navigation Items

```php
use Capell\Core\Contracts\Pageable;
use Capell\Navigation\Contracts\NavigationPageSyncer;

final class DemoNavigationPageSyncer implements NavigationPageSyncer
{
    public function removePageFromAllNavigations(Pageable $page): void
    {
        // Remove references to the page from package-owned navigation payloads.
    }
}
```

Bind a replacement only when another package owns the navigation payload shape. Otherwise use the default adapter.

## Cache Notes

Navigation changes clear frontend navigation cache keys through `NavigationObserver`. If another package caches rendered menus, it should clear those keys when it writes navigation items.

## Verification

```bash
vendor/bin/pest packages/navigation/tests --configuration=phpunit.xml
```
