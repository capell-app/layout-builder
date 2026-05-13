# Extending Publishing Studio

Publishing Studio owns workspaces, drafts, previews, approvals, publish checks, and release workspaces. Packages should extend it through registries, contributors, and tagged extenders. Do not query workspace internals from another package unless the contract below says that package owns the boundary.

## Extension Points

| Need                          | Extension point                                                                   |
| ----------------------------- | --------------------------------------------------------------------------------- |
| Make a model draftable        | `WorkspaceRegistry::register()`                                                   |
| Add release workspace rows    | `ReleaseWorkspaceItemRegistry::register()` with `ReleaseWorkspaceItemContributor` |
| Add workspace table actions   | `WorkspaceTableActionContributor::TAG`                                            |
| Extend page edit UI           | `PageEditExtender::TAG`                                                           |
| Extend page table UI          | `PageTableExtender::TAG`                                                          |
| Extend page export            | `PageExportExtender::TAG`                                                         |
| Extend page resource pages    | `PageResourcePageExtender::TAG`                                                   |
| Add publish checks            | `PublishCheckPipeline` / `PublishCheck` classes                                   |
| Integrate Migration Assistant | bind `MigrationAssistantContextResolver`                                          |

Core page, site, layout, language, media, translation, URL, and asset-relation models are registered by the package. Page-type models discovered later are also registered after Capell page types boot.

## Register a Draftable Model

Use `WorkspaceRegistry::register()` from the package that owns the model. The model table must have the workspace columns added by Publishing Studio migrations or a package migration.

```php
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\WorkspaceRegistry;
use Vendor\KnowledgeBase\Models\Article;

WorkspaceRegistry::register(
    Article::class,
    cloneUsing: static function (Article $article, Workspace $workspace): Article {
        $draft = $article->replicate();
        $draft->workspace_id = $workspace->getKey();
        $draft->shadowed_by_workspace_id = 0;
        $draft->save();

        return $draft;
    },
    finalizeOnPublish: static function (Article $draft): Article {
        $draft->published_at ??= now();
        $draft->save();

        return $draft;
    },
);
```

Leave `cloneUsing` and `finalizeOnPublish` null when the default copy-on-write behavior is enough.

## Add Release Workspace Items

Release workspaces show the content that will be published. Use a contributor when your package owns non-standard rows or needs package-specific labels.

```php
use Capell\PublishingStudio\Contracts\ReleaseWorkspaceItemContributor;
use Capell\PublishingStudio\Data\ReleaseWorkspaceItemData;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\ReleaseWorkspaceItemRegistry;
use Vendor\KnowledgeBase\Models\Article;

final class ArticleReleaseWorkspaceItemContributor implements ReleaseWorkspaceItemContributor
{
    public function itemsFor(Workspace $workspace): array
    {
        return Article::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $workspace->getKey())
            ->get()
            ->map(static fn (Article $article): ReleaseWorkspaceItemData => new ReleaseWorkspaceItemData(
                source: 'Knowledge base',
                label: $article->title,
                modelClass: Article::class,
                modelId: $article->getKey(),
                changeType: 'updated',
                status: 'ready',
                url: null,
            ))
            ->all();
    }
}

$this->app->singleton(ArticleReleaseWorkspaceItemContributor::class);

$this->app->afterResolving(ReleaseWorkspaceItemRegistry::class, static function (ReleaseWorkspaceItemRegistry $registry): void {
    $registry->register(ArticleReleaseWorkspaceItemContributor::class);
});
```

Return only rows that belong to the workspace passed to `itemsFor()`.

## Add Workspace Table Actions

Workspace table actions are tagged services. Keep actions narrow and permission-aware.

```php
use Capell\PublishingStudio\Contracts\WorkspaceTableActionContributor;
use Filament\Actions\Action;

final class ExportWorkspaceActionContributor implements WorkspaceTableActionContributor
{
    public function actions(): array
    {
        return [
            Action::make('export_workspace')
                ->label(__('host-app::publishing.export_workspace'))
                ->action(static function (): void {
                    // Call an Action owned by the host package.
                }),
        ];
    }
}

$this->app->singleton(ExportWorkspaceActionContributor::class);
$this->app->tag(ExportWorkspaceActionContributor::class, WorkspaceTableActionContributor::TAG);
```

Use the host package translation namespace for labels.

## Querying Workspace Models

Publishing Studio adds builder macros for registered draftable models:

```php
$liveArticles = Article::query()->live()->get();
$workspaceArticles = Article::query()->inWorkspace($workspace)->get();
$visibleArticles = Article::query()->forContext($workspace)->get();
```

Use these macros instead of hand-writing `workspace_id` checks in feature code.

## Cache Invalidation

Publishing publishes invalidate frontend cache through `InvalidatePublishedWorkspaceFrontendCacheAction`. Packages that render public pages from draftable models should make sure the model participates in HTML Cache dependency recording before relying on workspace invalidation.

## Verification

```bash
vendor/bin/pest packages/publishing-studio/tests --configuration=phpunit.xml
```
