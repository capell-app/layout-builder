<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Data\AiDiscoveryRenderContextData;
use Capell\SeoSuite\Enums\AiDiscoveryStatusEnum;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use InvalidArgumentException;
use LogicException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Generate llms.txt content for a site.
 *
 * @method static string run(AiDiscoveryRenderContextData|Site $context, ?Language $language = null)
 */
final class GenerateLlmsTxtAction
{
    use AsAction;

    public function handle(AiDiscoveryRenderContextData|Site $context, ?Language $language = null): string
    {
        $renderContext = $this->renderContext($context, $language);
        $siteProfile = ResolveAiDiscoveryProfileAction::run($renderContext->site, $renderContext->language);

        throw_unless($siteProfile instanceof AiDiscoverySiteProfile, LogicException::class, 'Resolving an AI Discovery site profile returned an unexpected page profile.');

        if (! $this->isEnabled($siteProfile)) {
            return '';
        }

        SyncAiDiscoveryPageProfilesAction::run($renderContext->site, $renderContext->language);

        $entries = BuildAiDiscoveryPageEntriesAction::run($renderContext, $siteProfile);

        $lines = ['# ' . $this->siteTitle($renderContext)];

        if (is_string($siteProfile->intro_markdown) && trim($siteProfile->intro_markdown) !== '') {
            $lines[] = '';
            $lines[] = trim($siteProfile->intro_markdown);
        }

        foreach ($entries->groupBy('section') as $section => $sectionEntries) {
            $lines[] = '';
            $lines[] = '## ' . strip_tags((string) $section);

            foreach ($sectionEntries as $entry) {
                $lines[] = $entry->toLlmsTxtLine();
            }
        }

        return rtrim(implode("\n", $lines)) . "\n";
    }

    private function renderContext(AiDiscoveryRenderContextData|Site $context, ?Language $language): AiDiscoveryRenderContextData
    {
        if ($context instanceof AiDiscoveryRenderContextData) {
            return $context;
        }

        throw_unless($language instanceof Language, InvalidArgumentException::class, 'A language is required when generating llms.txt from a site.');

        return new AiDiscoveryRenderContextData(site: $context, language: $language);
    }

    private function siteTitle(AiDiscoveryRenderContextData $context): string
    {
        return trim(strip_tags((string) $context->site->getMeta(
            'business_name',
            $context->site->translation?->title ?? config('app.name'),
        )));
    }

    private function isEnabled(AiDiscoverySiteProfile $siteProfile): bool
    {
        return $siteProfile->llms_txt_enabled && $siteProfile->status !== AiDiscoveryStatusEnum::Disabled;
    }
}
