<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Page;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Translation;
use Capell\SeoSuite\Data\AiDiscoveryRenderContextData;
use Capell\SeoSuite\Enums\AiDiscoveryStatusEnum;
use Capell\SeoSuite\Models\AiDiscoveryPageProfile;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use LogicException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static string run(AiDiscoveryRenderContextData $context, Page $page)
 */
final class GeneratePageMarkdownAction
{
    use AsAction;

    public function handle(AiDiscoveryRenderContextData $context, Page $page): string
    {
        $siteProfile = ResolveAiDiscoveryProfileAction::run($context->site, $context->language);

        throw_unless($siteProfile instanceof AiDiscoverySiteProfile, LogicException::class, 'Resolving an AI Discovery site profile returned an unexpected page profile.');

        if (! $siteProfile->markdown_pages_enabled || $siteProfile->status === AiDiscoveryStatusEnum::Disabled) {
            return '';
        }

        $pageProfile = ResolveAiDiscoveryPageProfileAction::run($page, $context->site, $context->language, $siteProfile);

        if (! $pageProfile->include_in_ai_index) {
            return '';
        }

        if ($context->siteDomain instanceof SiteDomain && $page->pageUrl !== null) {
            $page->pageUrl->setRelation('siteDomain', $context->siteDomain);
        }

        $override = trim((string) $pageProfile->markdown_override);

        if ($override !== '') {
            return rtrim($override) . "\n";
        }

        $translation = $page->translation;
        $title = $this->title($page, $translation);
        $summary = $this->summary($pageProfile, $translation);
        $body = trim($this->extractTextContent($translation));

        $lines = ['# ' . $title, ''];

        if ($summary !== '') {
            $lines[] = $summary;
            $lines[] = '';
        }

        if ($page->pageUrl?->full_url !== null && $page->pageUrl->full_url !== '') {
            $lines[] = 'Canonical: ' . $page->pageUrl->full_url;
            $lines[] = '';
        }

        if ($body !== '' && $body !== $summary) {
            $lines[] = $body;
            $lines[] = '';
        }

        return rtrim(implode("\n", $lines)) . "\n";
    }

    private function summary(AiDiscoveryPageProfile $profile, ?Translation $translation): string
    {
        $summary = trim(strip_tags((string) $profile->summary));

        if ($summary !== '') {
            return $summary;
        }

        $metaDescription = trim(strip_tags((string) $translation?->meta_description));

        if ($metaDescription !== '') {
            return $metaDescription;
        }

        return trim(strip_tags((string) $translation?->summary));
    }

    private function title(Page $page, ?Translation $translation): string
    {
        if (is_string($translation?->title) && $translation->title !== '') {
            return trim(strip_tags($translation->title));
        }

        if (is_string($translation?->label) && $translation->label !== '') {
            return trim(strip_tags($translation->label));
        }

        return trim(strip_tags($page->name));
    }

    private function extractableContent(?Translation $translation): string|array|null
    {
        $content = $translation?->content;

        return is_string($content) || is_array($content) ? $content : null;
    }

    private function extractTextContent(?Translation $translation): string
    {
        return RenderContentMarkdownAction::run($this->extractableContent($translation));
    }
}
