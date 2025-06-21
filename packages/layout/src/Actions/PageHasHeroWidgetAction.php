<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Core\Models\Page;
use Capell\Layout\Facades\Layout;
use Capell\Layout\Models\Widget;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static bool run(Page $page)
 */
class PageHasHeroWidgetAction
{
    use AsObject;

    public const HERO_KEY = 'hero';

    public function handle(Page $page): bool
    {
        static $cache = [];

        $cacheKey = spl_object_hash($page);

        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        if (! $page->layout) {
            return $cache[$cacheKey] = false;
        }

        if (! ($widget = $this->getHeroWidget($page)) instanceof Widget) {
            return $cache[$cacheKey] = false;
        }

        if ($widget->assets->isNotEmpty()) {
            return $cache[$cacheKey] = true;
        }

        if (! empty($page->translation?->meta[self::HERO_KEY])) {
            return $cache[$cacheKey] = preg_match('/<h1\b[^>]*>/i', (string) $page->translation->meta[self::HERO_KEY]) === 1;
        }

        return $cache[$cacheKey] = false;
    }

    private function getHeroWidget(Page $page): ?Widget
    {
        if (empty($page->layout->containers[self::HERO_KEY])) {
            return null;
        }

        foreach ($page->layout->containers[self::HERO_KEY]['widgets'] as $layoutWidget) {
            if ($layoutWidget['widget_key'] !== self::HERO_KEY) {
                continue;
            }

            return Layout::getContainerWidget(
                self::HERO_KEY,
                $layoutWidget['widget_key'],
                $layoutWidget['occurrence'] ?? 1
            );
        }

        return null;
    }
}
