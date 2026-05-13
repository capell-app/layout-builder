# Editable Regions

Frontend Authoring adds edit controls after the public page has loaded. Public Blade, cached HTML, theme output, and frontend assets must not contain editor markers.

## Built-in Registry

`EditableRegionRegistry` always starts from the current `PageUrl` and its translated page record. It registers these built-in regions:

| Region           | Field              | Selector config                                    |
| ---------------- | ------------------ | -------------------------------------------------- |
| Page title       | `title`            | `capell-frontend-authoring.selectors.page_title`   |
| Meta description | `meta.description` | `capell-frontend-authoring.selectors.page_title`   |
| Page content     | `content`          | `capell-frontend-authoring.selectors.page_content` |

Extra regions come from services tagged with `capell-frontend-authoring:editable-regions`.

## Register A Package Region

Register a callable from your package service provider. The callable receives `PageUrl` and returns `EditableRegionPayloadData` objects.

```php
use Capell\Core\Models\PageUrl;
use Capell\FrontendAuthoring\Data\EditableRegionPayloadData;

$this->app->tag([
    static function (PageUrl $pageUrl): array {
        $campaign = $pageUrl->pageable?->campaignLandingPage ?? null;

        if ($campaign === null) {
            return [];
        }

        return [
            new EditableRegionPayloadData(
                model: $campaign::class,
                recordKey: (int) $campaign->getKey(),
                field: 'headline',
                label: __('capell-campaign-studio::form.headline'),
                type: 'text',
                selector: '.campaign-hero__headline',
                currentUrl: $pageUrl->full_url,
            ),
        ];
    },
], 'capell-frontend-authoring:editable-regions');
```

Only use selectors that already exist for presentation. Do not add hidden editor-only attributes to public markup just to make authoring easier.

## Beacon And Signed Editor Route

| Route                            | Method                             | Purpose                                                                                 |
| -------------------------------- | ---------------------------------- | --------------------------------------------------------------------------------------- |
| `capell-frontend.beacon`         | `POST /beacon`                     | Resolves the current URL and returns authoring bootstrap only for authenticated admins. |
| `capell-frontend.authoring.edit` | `GET /authoring/regions/{payload}` | Opens one signed editor iframe for one editable field.                                  |

`EditableRegionSigner` signs the payload with `app.key` and creates a temporary signed route for 15 minutes. The payload includes model class, record key, field path, field type, selector, and current URL.

## Cache Invalidation

`UpdateEditableRegionAction` saves the field, then clears cached URLs recorded for the edited model. `ClearAffectedCachedUrlsAction` refreshes the current page when `capell-admin.auto_refresh_cache` is true or when the cleared URL is the page the admin edited.

If a package registers editable regions for a model that is never recorded in `cached_model_urls`, saves may work but stale cached pages can remain.

## Configuration

| Key                                                | Purpose                                                     |
| -------------------------------------------------- | ----------------------------------------------------------- |
| `capell-frontend-authoring.enabled`                | Enables the package surface.                                |
| `capell-frontend-authoring.selectors.page_title`   | Selector used for title and meta description edit controls. |
| `capell-frontend-authoring.selectors.page_content` | Selector used for page content editing.                     |

## Safety Checks

Anonymous and non-admin responses must not expose:

- authoring JavaScript or CSS
- editable selectors returned by the beacon
- model class names or IDs
- field paths
- permissions
- signed editor URLs
- package names

When a package changes frontend output, add or keep a test that checks anonymous and non-admin HTML for those strings.
