# AI-assisted SEO integration

AI-assisted content tools now live in **SEO Suite** (`capell-app/seo-suite`). Older internal notes may refer to an AIOrchestrator package; treat those as historical unless you are reading migration plans.

This page is for developers wiring or extending the current SEO Suite implementation.

## What you get

- Suggested page titles and meta descriptions.
- AI content briefs built from the live SEO report, including canonical, robots, schema, links, redirect, and Search Console context.
- Long-form page content drafts when enabled.
- AI image and layout draft helpers.
- Generation history with token usage and timings.
- Rate limiting, response parsing, and provider abstraction.
- Filament settings for prompts, limits, and provider defaults.

## Typical flow

1. Build a context object that implements `AiActionContextInterface`.
2. Call a SEO Suite action such as `SuggestPageTitlesAction` or `SuggestMetaDescriptionsAction`.
3. Show the suggestions in the admin.
4. Apply the selected draft with `ApplyAiDraftAction::run(...)`.
5. Record the generation in `ai_generation_histories`.

## Current architecture

| Layer              | Classes                                                                                                                                                                                            |
| ------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Actions            | `Capell\SeoSuite\Actions\SuggestPageTitlesAction`, `SuggestMetaDescriptionsAction`, `GenerateAiContentBriefAction`, `GeneratorPageContentAction`, `ApplyAiDraftAction`, `RecordAiGenerationAction` |
| Provider           | `Capell\SeoSuite\Support\PrismProvider`                                                                                                                                                            |
| Settings           | `Capell\SeoSuite\Settings\AIOrchestratorSettings`                                                                                                                                                  |
| Parsing and limits | `AiResponseParser`, `AiRateLimiter`, `AiTokenCounter`, `AiFeatureRegistry`                                                                                                                         |
| Persistence        | `Capell\SeoSuite\Models\AIGenerationHistory`, `AiCreatorContext`, `AiCreatorSession`                                                                                                               |
| Events             | `AiGenerationStarted`, `AiGenerationCompleted`, `AiGenerationFailed`                                                                                                                               |

## Context contract

Actions operate on a context rather than a concrete page class:

```php
interface AiActionContextInterface
{
    public function getContent(): string;

    public function getKeywords(): string;

    public function getPageId(): int;

    public function getLanguageId(): int;
}
```

Use this to adapt pages, articles, or another content source without coupling the action to one model.

## Example

```php
use Capell\SeoSuite\Actions\SuggestPageTitlesAction;

$titles = SuggestPageTitlesAction::run($context, [
    'user_id' => auth()->id(),
]);
```

Apply a selected draft:

```php
use Capell\SeoSuite\Actions\ApplyAiDraftAction;

ApplyAiDraftAction::run($page, $chosenText);
```

Generate a read-only content brief from the current SEO report:

```php
use Capell\SeoSuite\Actions\GenerateAiContentBriefAction;

$brief = GenerateAiContentBriefAction::run($page, $site, $language);
```

The brief returns structured fields for content angle, missing topics, headings, FAQ ideas, schema opportunities, internal links, and meta alternatives. The input report also includes passed checks, canonical URL, robots directives, redirect opportunities, and Search Console insights so the provider can suggest work that fits the actual page state.

## Configuration

Configuration lives in `config/capell-seo-suite.php`. Keep provider keys in environment variables, not in committed config.

Important areas:

| Config area             | Purpose                                                              |
| ----------------------- | -------------------------------------------------------------------- |
| Provider/model defaults | Choose the AI provider and default model                             |
| Prompts                 | Control system and user prompt templates                             |
| Rate limits             | Prevent noisy editor actions from flooding the provider              |
| Cache                   | Reuse identical suggestions where appropriate                        |
| Features                | Map feature keys to handler actions                                  |
| Publish gates           | Configure SEO check modes as blockers, warnings, or ignored findings |

## Filament integration

SEO Suite registers settings and admin extenders that add AI-assist controls where editors already write titles, descriptions, and page content.

The settings schema is `Capell\SeoSuite\Filament\Settings\AIOrchestratorSettingsSchema`.

`PageSeoPanel` also exposes an AI content brief action when AI is configured. The action is deliberately advisory: it records generation history and shows suggestions, but it does not automatically alter page content, metadata, schema, or links.

## Safety model

AI actions should receive bounded context and return structured data. `GenerateAiContentBriefAction` sends the page, site, language, SEO issues, passed checks, canonical URL, robots directives, schema dashboard-dashboard_reports, internal-link suggestions, redirect opportunities, and Search Console insights to the provider, then validates that the response is a JSON object before returning `AiContentBriefData`.

Keep generated output behind editor review. Use `ApplyAiDraftAction` only after a user has selected a draft, and keep any automatic publish decisions in deterministic checks such as SEO score, missing metadata, or schema coverage.

## Troubleshooting

| Symptom                | Check                                                                                |
| ---------------------- | ------------------------------------------------------------------------------------ |
| Suggestions are empty  | The context content is empty or the parser could not find the requested output shape |
| Brief generation fails | The provider returned non-JSON content or a required brief field was not parseable   |
| Rate limit exceeded    | Lower request frequency or adjust SEO Suite rate limits                              |
| Provider failures      | Check provider credentials, network access, and Capell logs                          |
| Repeated suggestions   | Clear the AI result cache or change content/keywords                                 |

## See also

- [SEO Suite README](../packages/seo-suite/README.md)
- [SEO metadata and discoverability](../packages/seo-suite/docs/seo-meta-and-discoverability.md)
- [SEO intelligence](../packages/seo-suite/docs/seo-intelligence.md)
- [Sitemaps](../packages/seo-suite/docs/sitemaps.md)
- [Test plan for actions and services](test-plan-actions-services.md)
