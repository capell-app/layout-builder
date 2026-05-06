<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Actions\Content\ExtractTextContentAction;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\SeoSuite\Data\AiContentBriefData;
use Capell\SeoSuite\Data\InternalLinkSuggestionData;
use Capell\SeoSuite\Data\PageSeoReportData;
use Capell\SeoSuite\Data\SchemaTemplateReportData;
use Capell\SeoSuite\Data\SearchConsoleInsightData;
use Capell\SeoSuite\Data\SeoIssueData;
use Capell\SeoSuite\Support\AiResponseParser;
use Capell\SeoSuite\Support\PrismProvider;
use Capell\SeoSuite\Support\PromptRepository;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static AiContentBriefData run(Page $page, Site $site, Language $language)
 */
final class GenerateAiContentBriefAction
{
    use AsAction;

    public function __construct(
        private readonly PromptRepository $prompts,
        private readonly PrismProvider $provider,
        private readonly AiResponseParser $parser,
    ) {}

    public function handle(Page $page, Site $site, Language $language): AiContentBriefData
    {
        $page->load([
            'translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->id),
        ]);
        $site->load([
            'translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->id),
        ]);

        $report = BuildPageSeoReportAction::run($page, $site, $language);
        $context = $this->context($page, $site, $language, $report);
        $prompt = $this->prompts->get('ai_content_brief') ?? [];
        $userMessage = strtr((string) ($prompt['user_template'] ?? ''), [
            '{{report}}' => json_encode($context['report'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            '{{page}}' => json_encode($context['page'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            '{{site}}' => json_encode($context['site'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            '{{language}}' => json_encode($context['language'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        ]);

        $params = [
            'model' => (string) ($prompt['model'] ?? config('capell-seo-suite.prism.model')),
            'messages' => [
                ['role' => 'system', 'content' => (string) ($prompt['system'] ?? '')],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'max_tokens' => (int) ($prompt['max_tokens'] ?? config('capell-seo-suite.prism.max_tokens', 4096)),
            'temperature' => (float) ($prompt['temperature'] ?? 0.3),
        ];

        $response = $this->provider->chat($params);
        $brief = $this->briefFromResponse($response->content);

        RecordAiGenerationAction::run([
            'action' => 'GenerateAiContentBriefAction',
            'model' => $response->model,
            'input' => json_encode($context, JSON_THROW_ON_ERROR),
            'output' => json_encode($brief->toArray(), JSON_THROW_ON_ERROR),
            'prompt_tokens' => (int) ($response->metadata['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($response->metadata['completion_tokens'] ?? 0),
            'total_tokens' => $response->tokensUsed,
            'duration' => $response->duration,
            'pageable_id' => $page->getKey(),
            'pageable_type' => $page->getMorphClass(),
            'language_id' => $language->getKey(),
            'metadata' => array_merge($response->metadata, [
                'ai_messages' => $params['messages'],
                'ai_params' => $params,
            ]),
        ]);

        return $brief;
    }

    /**
     * @return array<string, mixed>
     */
    private function context(Page $page, Site $site, Language $language, PageSeoReportData $report): array
    {
        /** @var Translation|null $translation */
        $translation = $page->translation;

        return [
            'page' => [
                'id' => $page->getKey(),
                'name' => $page->name,
                'title' => $translation?->title,
                'content' => ExtractTextContentAction::run($translation?->content, 2500),
                'meta' => $translation?->meta ?? [],
            ],
            'site' => [
                'id' => $site->getKey(),
                'name' => $site->name ?? null,
                'title' => $site->translation?->title ?? null,
            ],
            'language' => [
                'id' => $language->getKey(),
                'name' => $language->name,
                'code' => $language->code,
            ],
            'report' => [
                'score' => $report->score,
                'search_preview' => $report->searchPreview->toArray(),
                'social_preview' => $report->socialPreview->toArray(),
                'issues' => array_map(fn (SeoIssueData $issue): array => $issue->toArray(), $report->issues),
                'internal_link_suggestions' => array_map(
                    fn (InternalLinkSuggestionData $suggestion): array => $suggestion->toArray(),
                    $report->internalLinkSuggestions,
                ),
                'schema_dashboard-dashboard_reports' => array_map(
                    fn (SchemaTemplateReportData $schemaReport): array => $schemaReport->toArray(),
                    $report->schemaDashboardReports,
                ),
                'search_console_insights' => array_map(
                    fn (SearchConsoleInsightData $insight): array => $insight->toArray(),
                    $report->searchConsoleInsights,
                ),
            ],
        ];
    }

    private function briefFromResponse(string $content): AiContentBriefData
    {
        $this->parser->parse($content);

        $decoded = json_decode($this->stripJsonFence($content), true);

        throw_unless(is_array($decoded), InvalidArgumentException::class, 'AI content brief response must be a JSON object.');

        return new AiContentBriefData(
            contentAngle: $this->stringField($decoded, 'contentAngle'),
            missingTopics: $this->arrayField($decoded, 'missingTopics'),
            suggestedHeadings: $this->arrayField($decoded, 'suggestedHeadings'),
            faqIdeas: $this->arrayField($decoded, 'faqIdeas'),
            schemaOpportunities: $this->arrayField($decoded, 'schemaOpportunities'),
            internalLinks: $this->arrayField($decoded, 'internalLinks'),
            metaTitleAlternatives: $this->arrayField($decoded, 'metaTitleAlternatives'),
            metaDescriptionAlternatives: $this->arrayField($decoded, 'metaDescriptionAlternatives'),
        );
    }

    private function stripJsonFence(string $content): string
    {
        $content = trim($content);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $content, $matches) === 1) {
            return trim($matches[1]);
        }

        return $content;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function stringField(array $data, string $key): string
    {
        return is_scalar($data[$key] ?? null) ? trim((string) $data[$key]) : '';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, mixed>
     */
    private function arrayField(array $data, string $key): array
    {
        $value = $data[$key] ?? [];

        return is_array($value) ? array_values($value) : [];
    }
}
