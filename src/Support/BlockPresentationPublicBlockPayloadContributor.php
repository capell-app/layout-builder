<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\LayoutBuilder\Actions\ResolveBlockPresentationDataAction;
use Capell\LayoutBuilder\Contracts\PublicBlockPayloadContributor;
use Capell\LayoutBuilder\Models\Widget;

final class BlockPresentationPublicBlockPayloadContributor implements PublicBlockPayloadContributor
{
    /**
     * @var array<int, string|null>
     */
    private array $themeKeysById = [];

    public function priority(): int
    {
        return 5;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(Widget $block, Page $page, Language $language, string $containerKey, int $occurrence): array
    {
        $themeKey = $this->themeKey($page);

        return [
            'presentation' => ResolveBlockPresentationDataAction::run(
                block: $block,
                themeKey: is_string($themeKey) ? $themeKey : null,
            )->toArray(),
        ];
    }

    public function html(Widget $block, Page $page, Language $language, string $containerKey, int $occurrence): ?string
    {
        return null;
    }

    private function themeKey(Page $page): ?string
    {
        $layout = $page->relationLoaded('layout') ? $page->getRelation('layout') : null;
        $layoutThemeKey = $this->themeKeyFromLayout($layout);

        if ($layoutThemeKey !== null) {
            return $layoutThemeKey;
        }

        $site = $page->relationLoaded('site') ? $page->getRelation('site') : null;

        return $this->themeKeyFromSite($site);
    }

    private function themeKeyFromLayout(mixed $layout): ?string
    {
        if (! $layout instanceof Layout) {
            return null;
        }

        $theme = $layout->relationLoaded('theme') ? $layout->getRelation('theme') : null;
        $themeKey = $theme instanceof Theme ? $theme->getAttribute('key') : null;

        if (is_string($themeKey)) {
            return $themeKey;
        }

        $themeId = $layout->getAttribute('theme_id');

        return $this->themeKeyFromId($themeId);
    }

    private function themeKeyFromSite(mixed $site): ?string
    {
        if (! $site instanceof Site) {
            return null;
        }

        $theme = $site->relationLoaded('theme')
            ? $site->getRelation('theme')
            : null;
        $themeKey = $theme instanceof Theme ? $theme->getAttribute('key') : null;

        if (is_string($themeKey)) {
            return $themeKey;
        }

        return $this->themeKeyFromId($site->getAttribute('theme_id'));
    }

    private function themeKeyFromId(mixed $themeId): ?string
    {
        if (! is_int($themeId) && ! is_string($themeId)) {
            return null;
        }

        $themeId = (int) $themeId;
        if ($themeId < 1) {
            return null;
        }

        if (! array_key_exists($themeId, $this->themeKeysById)) {
            $themeKey = Theme::query()->whereKey($themeId)->value('key');
            $this->themeKeysById[$themeId] = is_string($themeKey) ? $themeKey : null;
        }

        return $this->themeKeysById[$themeId];
    }
}
