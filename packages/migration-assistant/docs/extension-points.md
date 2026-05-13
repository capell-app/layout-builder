# Migration Assistant Extension Points

Migration Assistant imports, exports, reviews, and rolls back content moves between Capell sites. Extension points should describe what a package owns, not patch the import job from the outside.

## Main Extension Points

| Need                       | Extension point                                           |
| -------------------------- | --------------------------------------------------------- |
| Read a new source format   | `ImportSourceReader` registered in `ImportSourceRegistry` |
| Add import targets         | `ImportTargetRegistry::register()`                        |
| Resolve imported relations | `RelationMatchResolverRegistry::register()`               |
| Contribute review rows     | `MigrationAssistantRowContributor`                        |
| Resolve package context    | `MigrationAssistantContextResolver`                       |
| Detect URL/page collisions | `PageCollisionDetector`                                   |
| Mark relation ownership    | `OwnershipMap::register()`                                |

The package ships CSV and XML readers, default targets for `page`, `type`, and `collection`, and relation resolvers for layouts, types, sites, and media.

## Register a Source Reader

```php
use Capell\MigrationAssistant\Contracts\ImportSourceReader;
use Capell\MigrationAssistant\Data\ExternalImportReadResult;
use Capell\MigrationAssistant\Support\ImportSourceRegistry;

final class JsonImportSourceReader implements ImportSourceReader
{
    public function supports(string $extension): bool
    {
        return $extension === 'json';
    }

    public function read(string $path): ExternalImportReadResult
    {
        $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return new ExternalImportReadResult(
            sourceType: 'json',
            columns: array_keys($payload[0] ?? []),
            rows: $payload,
            metadata: ['filename' => basename($path)],
        );
    }
}

$this->app->afterResolving(ImportSourceRegistry::class, static function (ImportSourceRegistry $registry): void {
    $registry->register(new JsonImportSourceReader, prepend: true);
});
```

Use `prepend: true` only when the new reader should win over a built-in reader for the same extension.

## Register an Import Target

```php
use Capell\MigrationAssistant\Support\ImportTargetRegistry;
use Vendor\KnowledgeBase\Models\Article;

$this->app->afterResolving(ImportTargetRegistry::class, static function (ImportTargetRegistry $registry): void {
    $registry->register('knowledge_article', Article::class);
});
```

Target keys become part of import payloads. Keep them stable once exported.

## Register Relation Resolvers

Relation resolvers are checked in priority order for each group.

```php
use Capell\MigrationAssistant\Services\Import\Resolvers\KeyedMatchResolver;
use Capell\MigrationAssistant\Services\Import\Resolvers\RelationMatchResolverRegistry;
use Vendor\KnowledgeBase\Models\Category;

$this->app->afterResolving(RelationMatchResolverRegistry::class, static function (RelationMatchResolverRegistry $registry): void {
    $registry->register('knowledge_categories', new KeyedMatchResolver(Category::class, keyColumn: 'slug'));
});
```

Use package-specific group names unless the resolver intentionally contributes to a core group such as `layouts`, `types`, `sites`, or `media`.

## Config Keys

| Key                              | Use                                                    |
| -------------------------------- | ------------------------------------------------------ |
| `migration-assistant.enabled`    | Enables the package surface.                           |
| `migration-assistant.disk`       | Storage disk for import/export files.                  |
| `migration-assistant.channels`   | Notification channels for completed or failed imports. |
| `migration-assistant.completed`  | Completion notification settings.                      |
| `migration-assistant.failed`     | Failure notification settings.                         |
| `migration-assistant.connection` | Queue/database connection setting where configured.    |

The package also has model, table, and path config used by import internals. Document those only when a host app needs to change them.

## Verification

```bash
vendor/bin/pest packages/migration-assistant/tests --configuration=phpunit.xml
```
