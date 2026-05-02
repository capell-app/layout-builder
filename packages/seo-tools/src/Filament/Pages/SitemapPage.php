<?php

declare(strict_types=1);

namespace Capell\SeoTools\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\SeoTools\Support\Sitemap\SitemapBuilder;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class SitemapPage extends Page
{
    use HasPageShield;

    #[Url]
    public ?int $language_id = null;

    #[Url]
    public ?int $site_id = null;

    protected ?Collection $site_languages = null;

    protected $sitemap;

    protected array|Collection $sites = [];

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Map;

    protected static ?string $slug = 'sitemap';

    protected string $view = 'capell::components.pages.sitemap';

    public static function getNavigationLabel(): string
    {
        return __('capell-admin::generic.sitemap');
    }

    public function getSitemap(): ?Collection
    {
        $site = null;
        $domain = null;

        if ($this->site_id !== null && $this->language_id !== null) {
            $site = $this->getSites()->firstWhere('id', $this->site_id);

            if ($site === null || ! ($site instanceof Site)) {
                return null;
            }

            $domain = $site->siteDomains->firstWhere('language_id', $this->language_id);
        }

        if (! $site instanceof Site || $domain === null || $domain->language === null) {
            return null;
        }

        $sitemapLoader = new SitemapBuilder(
            site: $site,
            domain: $domain,
            language: $domain->language,
            withEditUrl: true,
        );

        return $sitemapLoader->build();
    }

    public function getTitle(): string|Htmlable
    {
        return __('capell-admin::generic.sitemap');
    }

    public function mount(): void
    {
        $this->sites = $this->getSites();

        $this->site_id = request()->integer('site_id');

        if ($this->site_id === 0) {
            $this->site_id = $this->getDefaultSite()?->id;
        }

        $this->site_languages = $this->getSiteLanguage();

        $this->language_id = request()->integer('language_id');

        if ($this->language_id === 0) {
            $this->language_id = $this->getDefaultSiteLanguage()?->id;
        }
    }

    public function updatedSiteId(): void
    {
        $this->getSiteLanguage();
        $this->language_id = $this->getDefaultSiteLanguage()?->id;
    }

    /**
     * @return Collection<Site>
     */
    protected function fetchSites(): Collection
    {
        /** @var class-string<Site> $model */
        $model = Site::class;

        return $model::query()
            ->with([
                'languages',
                'translations.language',
                'siteDomains.language',
            ])
            ->ordered()
            ->get();
    }

    protected function getDefaultSite(): ?Site
    {
        $sites = $this->getSites();

        $site = $this->site_id !== null && $this->site_id !== 0 ? $sites->firstWhere('id', $this->site_id) : $sites->firstWhere('default', true);

        if ($site === null || ! ($site instanceof Site)) {
            $first = $sites->first();

            return $first instanceof Site ? $first : null;
        }

        return $site;
    }

    protected function getDefaultSiteLanguage(): ?Language
    {
        if (! $this->site_languages instanceof Collection || $this->site_languages->isEmpty()) {
            return null;
        }

        foreach ($this->site_languages as $language) {
            if ($language->default) {
                return $language;
            }
        }

        $first = $this->site_languages->first();

        return $first instanceof Language ? $first : null;
    }

    protected function getSiteLanguage(): ?Collection
    {
        if ($this->site_id === null || $this->site_id === 0) {
            return null;
        }

        $sites = $this->getSites();

        $site = $sites->firstWhere('id', $this->site_id);

        $this->site_languages = $site->languages;

        return $this->site_languages;
    }

    /**
     * @return Collection<Site>
     */
    protected function getSites(): Collection
    {
        if ((is_array($this->sites) && $this->sites === []) || ($this->sites instanceof Collection && $this->sites->isEmpty())) {
            $this->sites = $this->fetchSites();
        }

        return $this->sites;
    }

    protected function getViewData(): array
    {
        return [
            'sites' => $this->getSites(),
            'site_languages' => $this->getSiteLanguage(),
            'sitemap' => $this->getSitemap(),
        ];
    }
}
