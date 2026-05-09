<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Models\AiDiscoveryPageProfile;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use LogicException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static AiDiscoveryPageProfile run(Page $page, Site $site, Language $language, array $data)
 */
final class UpdateAiDiscoveryPageProfileAction
{
    use AsAction;

    private const MAX_STRING_LENGTH = 255;

    /**
     * @param  array{include_in_ai_index?: mixed, section?: mixed, priority?: mixed, summary?: mixed, markdown_override?: mixed, exclude_reason?: mixed}  $data
     */
    public function handle(Page $page, Site $site, Language $language, array $data): AiDiscoveryPageProfile
    {
        $profile = $this->profileFor($page, $site, $language);
        $includeInAiIndex = (bool) ($data['include_in_ai_index'] ?? $profile->include_in_ai_index);
        $section = $this->stringValue($data['section'] ?? $profile->section);

        $profile->fill([
            'include_in_ai_index' => $includeInAiIndex,
            'section' => $section !== '' ? $section : 'Pages',
            'priority' => max(0, min(1000, (int) ($data['priority'] ?? $profile->priority))),
            'summary' => $this->nullableStringValue($data, 'summary', $profile->summary),
            'markdown_override' => $this->nullableStringValue($data, 'markdown_override', $profile->markdown_override),
            'exclude_reason' => $includeInAiIndex
                ? null
                : $this->nullableStringValue($data, 'exclude_reason', $profile->exclude_reason, self::MAX_STRING_LENGTH),
        ]);

        $profile->save();

        return $profile;
    }

    private function profileFor(Page $page, Site $site, Language $language): AiDiscoveryPageProfile
    {
        $siteProfile = ResolveAiDiscoveryProfileAction::run($site, $language);

        throw_unless($siteProfile instanceof AiDiscoverySiteProfile, LogicException::class, 'AI Discovery site profile could not be resolved.');

        $pageProfile = ResolveAiDiscoveryProfileAction::run($site, $language, $page);

        throw_unless($pageProfile instanceof AiDiscoveryPageProfile, LogicException::class, 'AI Discovery page profile could not be resolved.');

        return $pageProfile;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function nullableStringValue(array $data, string $key, ?string $currentValue, ?int $maxLength = null): ?string
    {
        if (! array_key_exists($key, $data)) {
            return $currentValue;
        }

        return $this->nullableString($data[$key], $maxLength);
    }

    private function nullableString(mixed $value, ?int $maxLength = null): ?string
    {
        $stringValue = $this->stringValue($value, $maxLength);

        return $stringValue !== '' ? $stringValue : null;
    }

    private function stringValue(mixed $value, ?int $maxLength = self::MAX_STRING_LENGTH): string
    {
        $stringValue = is_scalar($value) ? trim((string) $value) : '';

        return $maxLength === null ? $stringValue : mb_substr($stringValue, 0, $maxLength);
    }
}
