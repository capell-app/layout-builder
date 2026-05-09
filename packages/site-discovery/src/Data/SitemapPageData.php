<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Data;

use Capell\Core\Actions\GetEditPageResourceUrlAction;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Exceptions\UrlMissingSiteDomainException;
use Capell\Core\Models\Page;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

class SitemapPageData extends Data
{
    public function __construct(
        public string $label,
        public string $url,
        /** @var Collection<int, SitemapPageData> */
        public ?Collection $children = null,
        #[WithCast(DateTimeInterfaceCast::class, DATE_ATOM)]
        #[WithTransformer(DateTimeInterfaceTransformer::class, DATE_ATOM)]
        public ?CarbonImmutable $lastModified = null,
        public ?string $changeFrequency = null,
        public ?float $priority = 0.5,
        public ?string $editUrl = null,
        #[MapInputName('pageable_type')]
        public ?string $pageableType = null,
        #[MapInputName('pageable_id')]
        public ?int $pageId = null,
    ) {}

    public static function fromPage(Pageable $page, bool $withEditUrl = false): self
    {
        $page->loadMissing([
            'translation',
            'type',
            'pageUrl.siteDomain',
        ]);

        if ($page->hasPageHierarchy()) {
            $page->loadMissing([
                'children' => [
                    'translation',
                    'type',
                    'pageUrl.siteDomain',
                ],
            ]);
        }

        return new self(
            label: $page->translation?->label ?? $page->name,
            url: self::pageUrl($page),
            children: $page->hasPageHierarchy()
                ? $page->children
                    ?->map(fn (Page $child): SitemapPageData => self::fromPage($child, withEditUrl: $withEditUrl))
                    ->values()
                : null,
            lastModified: self::resolveLastModified($page),
            changeFrequency: $page->meta['cache_time'] ?? 'always',
            priority: $page->meta['priority'] ?? 0.5,
            editUrl: $withEditUrl ? GetEditPageResourceUrlAction::run($page) : null,
            pageableType: $page->getMorphClass(),
            pageId: $page->id,
        );
    }

    public static function resolveLastModified(Pageable $page): CarbonImmutable
    {
        $date = $page->published_at ?? $page->visible_from ?? $page->created_at ?? now();

        return CarbonImmutable::make($date);
    }

    private static function pageUrl(Pageable $page): string
    {
        try {
            return $page->pageUrl->full_url;
        } catch (UrlMissingSiteDomainException) {
            return $page->pageUrl->url ?? '';
        }
    }
}
