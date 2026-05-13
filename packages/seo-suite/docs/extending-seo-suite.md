# Extending SEO Suite

SEO Suite has several extension points. Use them from a package service provider; do not reach into Filament resources or SEO Suite internals.

## Extension Points

| Need                         | Extension point                             | Notes                                                                                                                                               |
| ---------------------------- | ------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| Add structured data          | `SchemaTemplateRegistry` + `SchemaTemplate` | Use `registerIfMissing()` for defaults, `register()` for package-owned schema, and `replace()` only when intentionally overriding another template. |
| Add AI Creator section types | `SectionRegistry`                           | Describes section keys that AI Creator may emit. The pipeline rejects unregistered section types.                                                   |
| Add AI content targets       | tag `capell-seo-suite:content-targets`      | Targets implement `ContentTargetContract` and apply generated sections to a model or JSON boundary.                                                 |
| Add page SEO form fields     | `PageSchemaExtender::TAG`                   | Used by `SearchMetaSchemaExtender`, `PageSeoSettingsTabExtender`, and `PageSeoPanelSchemaExtender`.                                                 |
| Add site SEO fields          | `SiteSchemaExtender::TAG`                   | Used by site-level translation and details meta extenders.                                                                                          |
| Add frontend SEO output      | `RenderHookRegistry`                        | `RegisterSeoHeadHooks` writes SEO meta and schema near `RenderHookLocation::HeadClose`.                                                             |
| Add publish checks           | `SeoPublishReportProvider`                  | Publishing Studio can consume SEO Suite publish reports when both packages are installed.                                                           |

SEO Suite also registers settings groups for `ai-orchestrator`, `seo_suite`, and `frontend`. Keep new settings inside those groups unless the feature clearly belongs somewhere else.

## Register a Schema Template

Schema templates build JSON-LD nodes for pages whose type metadata matches a `SchemaTemplateTypeEnum`.

```php
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Contracts\SchemaTemplate;
use Capell\SeoSuite\Enums\SchemaTemplateTypeEnum;
use Capell\SeoSuite\Support\SchemaTemplates\SchemaTemplateRegistry;

$this->app->afterResolving(SchemaTemplateRegistry::class, static function (SchemaTemplateRegistry $registry): void {
    $registry->registerIfMissing(SchemaTemplateTypeEnum::Event, new class implements SchemaTemplate
    {
        public function build(Page $page, Site $site, Language $language): array
        {
            return [
                '@type' => 'Event',
                'name' => (string) data_get($page, 'title'),
                'url' => (string) data_get($page, 'url'),
            ];
        }

        public function requiredFields(Page $page, Site $site, Language $language): array
        {
            return ['@type', 'name', 'url'];
        }
    });
});
```

Use `registerIfMissing()` when the package is providing a fallback. Use `register()` when duplicate registration should fail loudly.

## Register AI Creator Sections

`SectionRegistry` tells AI Creator which section keys are valid. The descriptor is prompt-facing, so keep it short and factual.

```php
use Capell\SeoSuite\Support\SectionRegistry;

$this->app->afterResolving(SectionRegistry::class, static function (SectionRegistry $registry): void {
    $registry->register('case-study-hero', [
        'label' => 'Case study hero',
        'description' => 'Lead section for a client case study.',
        'good_for' => ['case studies', 'proof pages'],
        'not_for' => ['product listing pages'],
        'fields' => ['heading', 'summary', 'client_name'],
        'media' => ['client_logo'],
        'supports_translations' => true,
        'repeatable' => false,
    ]);
});
```

If AI Creator returns a section key that is not registered, `AiCreatorPipeline` throws before anything is applied.

## Register a Content Target

Content targets let AI Creator write generated sections somewhere other than the built-in flat JSON target.

```php
use Capell\SeoSuite\Contracts\ContentTargetContract;
use Capell\SeoSuite\Models\AiCreatorSession;

final class LandingPageContentTarget implements ContentTargetContract
{
    public function handles(): string
    {
        return 'landing-page';
    }

    public function apply(array $sections, AiCreatorSession $session): void
    {
        $session->forceFill([
            'generated_payload' => ['sections' => $sections],
        ])->save();
    }
}

$this->app->singleton(LandingPageContentTarget::class);
$this->app->tag(LandingPageContentTarget::class, 'capell-seo-suite:content-targets');
```

Keep targets idempotent where possible. AI retries should not duplicate content.

## Config Worth Documenting

Do not document every nested crawler or prompt key in public docs. Start with the keys developers are most likely to touch:

| Key                               | Use                                                                               |
| --------------------------------- | --------------------------------------------------------------------------------- |
| `capell-seo-suite.features`       | Enables or disables AI and SEO feature groups.                                    |
| `capell-seo-suite.prism`          | Provider and retry settings for AI calls.                                         |
| `capell-seo-suite.prompts`        | Prompt templates used by title, meta description, content, and layout generation. |
| `capell-seo-suite.rate_limiting`  | AI request throttling.                                                            |
| `capell-seo-suite.cache.ttl`      | AI generation cache TTL.                                                          |
| `capell-seo-suite.ai_discovery`   | `llms.txt`, Markdown output, crawler rules, and AI Discovery defaults.            |
| `capell-seo-suite.search_console` | Google Search Console client settings.                                            |

Crawler user-agent lists and provider defaults are package-maintained implementation data. Mention them in AI Discovery docs, but do not force every key into setup prose.

## Verification

Run the SEO Suite package tests after changing schema, AI Creator, Search Console, or publish-gate behavior:

```bash
vendor/bin/pest packages/seo-suite/tests --configuration=phpunit.xml
```
