# Schema Templates

Schema templates make structured-data requirements explicit for each page type.

## Registry

`SchemaTemplateRegistry` stores implementations of `Capell\SeoTools\Contracts\SchemaTemplate` by `SchemaTemplateTypeEnum`.

Use:

- `register()` when duplicate registration should fail loudly.
- `replace()` when a project intentionally overrides the default template.
- `registerIfMissing()` when a package wants to provide a default without blocking host projects.

The SEO Tools service provider registers default `WebPageSchemaTemplate` and `ArticleSchemaTemplate` implementations.

## Matching

The registry reads the page schema type from `type.meta.schema.type` and returns templates whose enum value matches that type. `BuildSchemaTemplateReportAction` turns those matches into report rows for the editor panel and SEO audit.

## Graph output

Templates work with `SchemaGraphAction`, which emits a single JSON-LD `@graph`. The graph keeps stable `@id` values for organization, website, page, article, image, and breadcrumb entities so relationships survive URL changes.

## Extending

Create a class that implements `SchemaTemplate`, then register it from a service provider:

```php
use Capell\SeoTools\Enums\SchemaTemplateTypeEnum;
use Capell\SeoTools\Support\SchemaTemplates\SchemaTemplateRegistry;

public function boot(SchemaTemplateRegistry $schemaTemplateRegistry): void
{
    $schemaTemplateRegistry->replace(
        SchemaTemplateTypeEnum::Article,
        new CustomArticleSchemaTemplate(),
    );
}
```

Keep template classes focused on one schema shape. If a page needs multiple entities, compose them through the graph action rather than returning ad hoc arrays from UI code.
