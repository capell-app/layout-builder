<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\Creator;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Events\NavigationCreating;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NavigationCreator
{
    /**
     * @var class-string<Navigation>
     */
    private readonly string $navigationModel;

    /**
     * @var class-string<Type>
     */
    private readonly string $typeModel;

    public function __construct()
    {
        $this->navigationModel = Navigation::class;
        $this->typeModel = Type::class;
    }

    public static function getPageNavigationLabel(Page $page, ?Language $language = null): ?string
    {
        if (! $language instanceof Language) {
            $language = $page->site->language;
        }

        $translation = $page->translations->firstWhere('language_id', $language->id);

        return $translation?->label ?? $page->name;
    }

    public function footerNavigation(
        Site $site,
        ?Type $type = null,
        ?Language $language = null,
        ?Collection $pages = null,
        array $items = [],
        string $key = NavigationHandle::Footer->value,
    ): Navigation {
        if (! $language instanceof Language) {
            $language = $site->language;
        }

        $navigation = self::createNavigation($key, $site, $language, $type);

        $items = collect($navigation->items)
            ->merge($items);

        if ($pages instanceof Collection && $pages->isNotEmpty()) {
            $pages->each(function (Page $page) use (&$items, $language): void {
                $existingItem = $items->first(fn (array $candidate): bool => isset($candidate['data']['pageable_id'], $candidate['data']['pageable_type'])
                        && (int) $candidate['data']['pageable_id'] === $page->getKey()
                        && $candidate['data']['pageable_type'] === $page->getMorphClass());

                if ($existingItem !== null) {
                    return;
                }

                $items->put((string) Str::uuid(), [
                    'label' => self::getPageNavigationLabel($page, $language),
                    'type' => NavigationItemType::Page->value,
                    'data' => [
                        'site_id' => $page->site_id,
                        'pageable_id' => $page->getKey(),
                        'pageable_type' => $page->getMorphClass(),
                    ],
                    'children' => [],
                ]);
            });
        }

        event(new NavigationCreating($navigation, $items));

        $navigation->items = $items;
        $navigation->save();

        return $navigation;
    }

    public function subFooterNavigation(
        Site $site,
        ?Type $type = null,
        ?Language $language = null,
        ?Collection $pages = null,
        array $items = [],
        string $key = NavigationHandle::SubFooter->value,
    ): Navigation {
        return $this->footerNavigation(site: $site, type: $type, language: $language, pages: $pages, items: $items, key: $key);
    }

    public function mainNavigation(
        Site $site,
        ?Type $type = null,
        ?Language $language = null,
        ?Page $home = null,
        array $additionalItems = [],
        string $key = NavigationHandle::Main->value,
    ): Navigation {
        if (! $language instanceof Language) {
            $language = $site->language;
        }

        $navigation = self::createNavigation($key, $site, $language, $type);

        $items = collect($navigation->items);

        $homePageExists = $items->first(
            fn (array $candidate): bool => isset($candidate['data']['pageable_id'], $candidate['data']['pageable_type'])
                && (int) $candidate['data']['pageable_id'] === $home->getKey()
                && $candidate['data']['pageable_type'] === $home->getMorphClass(),
        );

        if ($home instanceof Page && $homePageExists === null) {
            $items->prepend(
                [
                    'label' => self::getPageNavigationLabel($home, $language) ?? __('capell::generic.home'),
                    'type' => NavigationItemType::Page->value,
                    'data' => [
                        'site_id' => $home->site_id,
                        'pageable_id' => $home->id,
                        'pageable_type' => $home->getMorphClass(),
                        'hidden_label' => true,
                        'icon' => 'heroicon-o-home',
                    ],
                    'children' => [],
                ],
                (string) Str::uuid(),
            );
        }

        foreach ($additionalItems as $item) {
            if (isset($item['data']['pageable_id'], $item['data']['pageable_type'])) {
                $pageExists = $items->first(
                    fn (array $candidate): bool => isset($candidate['data']['pageable_id'], $candidate['data']['pageable_type'])
                        && (int) $candidate['data']['pageable_id'] === (int) $item['data']['pageable_id']
                        && $candidate['data']['pageable_type'] === $item['data']['pageable_type'],
                );

                if ($pageExists !== null) {
                    continue;
                }
            }

            $items->put((string) Str::uuid(), [
                'label' => $item['label'],
                'type' => $item['type'],
                'data' => $item['data'],
                'children' => $item['children'] ?? [],
            ]);
        }

        $navigation->items = $items->toArray();

        $navigation->save();

        return $navigation;
    }

    /**
     * @return Builder<Navigation>
     */
    private function navigationQuery(): Builder
    {
        return $this->navigationModel::query();
    }

    /**
     * @return Builder<Type>
     */
    private function typeQuery(): Builder
    {
        return $this->typeModel::query();
    }

    private function createNavigation(string $key, Site $site, ?Language $language = null, ?Type $type = null): Navigation
    {
        $navigation = $this->navigationModel::query()
            ->where([
                'key' => $key,
                'site_id' => $site->id,
            ])
            ->when(
                $language instanceof Language,
                fn (Builder $query): Builder => $query->where('language_id', $language->id),
                fn (Builder $query): Builder => $query->whereNull('language_id'),
            )
            ->first();

        if ($navigation !== null) {
            return $navigation;
        }

        // Use typed Type builder so scopes are recognized
        $type ??= $this->typeQuery()->navigationType()->first();
        if ($type === null) {
            $type = $this->typeQuery()->create([
                'key' => 'navigation',
                'type' => 'navigation',
                'name' => 'Navigation',
            ]);
        }

        return $this->navigationQuery()->make([
            'name' => Str::title($key),
            'type_id' => $type->id,
            'key' => $key,
            'site_id' => $site->id,
            'language_id' => $language?->id,
        ]);
    }
}
