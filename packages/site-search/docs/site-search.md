# Site Search Reference

Site Search is deliberately small: it defines a `SiteSearch` contract, DTO result shape, and two drivers. Public routes, search pages, and theme-specific UI can depend on the contract without caring whether the project uses SQL LIKE queries or a dedicated search engine.

## Contract

```php
use Capell\SiteSearch\Contracts\SiteSearch;

$results = app(SiteSearch::class)->search(
    query: 'pricing',
    perPage: 10,
    page: 1,
);

$highlighted = app(SiteSearch::class)->highlight('Pricing plans', 'pricing');
```

`search()` returns a Laravel `LengthAwarePaginator` containing `SearchResultData` items.

## Result data

`SearchResultData` carries:

| Field     | Purpose                           |
| --------- | --------------------------------- |
| `title`   | Display title                     |
| `url`     | Public result URL                 |
| `excerpt` | Short result text                 |
| `type`    | Result type label, usually `page` |
| `score`   | Driver-specific relevance score   |

## Database driver

`DatabaseSiteSearch` is the default driver. It:

- trims queries and ignores values shorter than two characters;
- caps `perPage` between 1 and 100;
- escapes `%`, `_`, and `!` before building LIKE conditions;
- searches configured columns with `LIKE ... ESCAPE '!'`;
- maps rows into `SearchResultData`.

Use it for small sites, local development, or projects that do not need language-aware ranking.

## Scout driver

`ScoutSiteSearch` delegates search to a model using Laravel Scout:

```php
use App\Models\SearchablePage;
use Capell\SiteSearch\Contracts\SiteSearch;
use Capell\SiteSearch\Drivers\ScoutSiteSearch;

$this->app->bind(
    SiteSearch::class,
    fn (): SiteSearch => new ScoutSiteSearch(
        modelClass: SearchablePage::class,
        urlColumn: 'slug',
        typeColumn: 'type',
    ),
);
```

Use Scout when the project needs Meilisearch, Algolia, Typesense, custom ranking, typo tolerance, or faster cross-field search.

## Admin hooks

The provider is prepared for optional admin analytics classes such as dashboard widgets, settings contributors, and a purge command. Those registrations are guarded by `class_exists()` so minimal installs can use only the contract and drivers.

## Testing

The package has focused unit tests for:

- database query escaping, pagination, and DTO mapping;
- Scout result mapping;
- `SearchResultData` shape.
