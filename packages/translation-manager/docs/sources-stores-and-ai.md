# Sources, Stores, And AI

Translation Manager reads translation sources, compares locale files, writes selected entries, and optionally asks AI for suggestions. The package binds three contracts by default:

- `TranslationSourceResolver` -> `ConfigTranslationSourceResolver`
- `TranslationFileStore` -> `FileTranslationFileStore`
- `TranslationAITranslator` -> `NullTranslationAITranslator`

## Config Keys

| Key                                                | Use                                                 |
| -------------------------------------------------- | --------------------------------------------------- |
| `capell-translation-manager.source_locale`         | Default source locale.                              |
| `capell-translation-manager.locale_pattern`        | Allowed locale format.                              |
| `capell-translation-manager.app_source`            | Application translation source.                     |
| `capell-translation-manager.package_paths`         | Glob paths scanned for package translation folders. |
| `capell-translation-manager.vendor_namespaces`     | Additional vendor namespaces.                       |
| `capell-translation-manager.package_source_writes` | Allows writes back to package sources.              |

Keep `package_source_writes` false unless the host app intentionally edits package language files.

## Add Translation Sources

The default resolver reads config. Override `TranslationSourceResolver` only when sources come from another registry.

```php
use Capell\TranslationManager\Contracts\TranslationSourceResolver;
use Capell\TranslationManager\Data\TranslationSourceData;

final class DemoTranslationSourceResolver implements TranslationSourceResolver
{
    public function sources(): array
    {
        return [
            new TranslationSourceData(
                key: 'demo',
                label: 'Demo',
                sourcePath: base_path('packages/demo/resources/lang'),
                overridePath: resource_path('lang/vendor/demo'),
                namespace: 'demo',
                type: 'package',
                sourceWritable: false,
            ),
        ];
    }

    public function source(string $key): TranslationSourceData
    {
        return collect($this->sources())->firstOrFail(
            static fn (TranslationSourceData $source): bool => $source->key === $key,
        );
    }
}
```

## Add an AI Translator

```php
use Capell\TranslationManager\Contracts\TranslationAITranslator;
use Capell\TranslationManager\Data\AITranslationSuggestionData;
use Capell\TranslationManager\Data\TranslationEntryData;

final class DemoTranslationAITranslator implements TranslationAITranslator
{
    public function available(): bool
    {
        return true;
    }

    public function translateSelected(string $sourceLocale, string $targetLocale, array $entries): array
    {
        return array_map(
            static fn (TranslationEntryData $entry): AITranslationSuggestionData => new AITranslationSuggestionData(
                key: $entry->key,
                value: '[' . $targetLocale . '] ' . $entry->sourceValue,
            ),
            $entries,
        );
    }
}

$this->app->singleton(TranslationAITranslator::class, DemoTranslationAITranslator::class);
```

Use the AI Orchestrator integration when it is installed. Bind a custom translator only when another provider owns translation.

## Verification

```bash
vendor/bin/pest packages/translation-manager/tests --configuration=phpunit.xml
```
