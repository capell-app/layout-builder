<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\MediaConversionEnum;
use Capell\Core\Exceptions\SiteDomainNotFoundException;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Enums\SchemaEntityTypeEnum;
use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @method static array run(Site $site, Language $language)
 */
class SiteMetaSchemaAction
{
    use AsAction;

    protected string $site_domain;

    protected Site $site;

    protected Language $language;

    public function handle(Site $site, Language $language): array
    {
        if ($site->siteDomain === null) {
            throw new SiteDomainNotFoundException('Site domain not found for site ID ' . $site->id);
        }

        $this->site_domain = $site->siteDomain->full_url;
        $this->site = $site;
        $this->language = $language;

        return $this->process(
            type: $site->meta['organization_type'] ?? 'Organization',
            name: $site->meta['business_name'] ?? $site->translation->title,
            alternateName: $site->translation->title,
            areasServed: $site->meta['areas_served'] ?? [],
            currenciesAccepted: $site->meta['currencies_accepted'] ?? null,
            description: $site->translation->meta['description'] ?? '',
            email: $site->meta['email'] ?? null,
            logo: $site->logo,
            media: $site->media,
            openHours: $site->meta['open_hours'] ?? [],
            paymentAccepted: $site->meta['payment_accepted'] ?? null,
            phone: $site->meta['phone'] ?? null,
            priceRange: $site->meta['price_range'] ?? null,
            socialLinks: $site->meta['social_links'] ?? [],
        );
    }

    protected function areasServed(array $areas_served): array
    {
        $return = [];

        foreach ($areas_served as $area_served) {
            if (isset($area_served['type']) && $area_served['type'] !== null && $area_served['type'] !== '') {
                $item = [
                    '@type' => $area_served['type'],
                    'name' => $area_served['name'],
                ];

                if (isset($area_served['url']) && $area_served['url'] !== null && $area_served['url'] !== '') {
                    $item['@id'] = $area_served['url'];
                }

                $return[] = $item;
            } else {
                $return[] = $area_served['name'];
            }
        }

        return $return;
    }

    protected function availableLanguage(array $languages): array
    {
        $return = [];

        foreach ($languages as $language_id) {
            $siteTranslation = $this->site->translations->firstWhere('language_id', $language_id);
            if ($siteTranslation === null) {
                continue;
            }

            if ($siteTranslation->language === null) {
                continue;
            }

            $return[] = $siteTranslation->language->name;
        }

        return $return;
    }

    protected function contactPoint(
        ?array $areas_served = null,
        ?string $email = null,
        ?Page $contactPage = null,
        ?array $languages = null,
        ?string $name = null,
        ?array $open_hours = null,
        ?array $options = null,
        ?string $phone = null,
        ?array $social_links = null,
        ?string $type = null,
    ): array {
        $return = [
            '@type' => 'ContactPoint',
        ];

        if (! in_array($name, [null, '', '0'], true)) {
            $return['name'] = $name;
        }

        if (! in_array($email, [null, '', '0'], true)) {
            $return['email'] = $email;
        }

        if (! in_array($phone, [null, '', '0'], true)) {
            $return['telephone'] = $phone;
        }

        if ($contactPage instanceof Pageable) {
            $return['url'] = $contactPage->pageUrl->full_url;
        }

        if (! in_array($type, [null, '', '0'], true)) {
            $return['contactType'] = $type;
        }

        if ($options !== null && $options !== []) {
            $return['contactOption'] = $options;
        }

        if ($open_hours !== null && $open_hours !== []) {
            $return['hoursAvailable'] = $this->openingHoursSpecification($open_hours);
        }

        if ($social_links !== null && $social_links !== []) {
            $return['sameAs'] = $this->sameAs($social_links);
        }

        if ($areas_served !== null && $areas_served !== []) {
            $return['areaServed'] = $this->areasServed($areas_served);
        }

        if ($languages !== null && $languages !== []) {
            $return['availableLanguage'] = $this->availableLanguage($languages);
        }

        return $return;
    }

    protected function contactPoints(array $contacts, ?Page $contactPage = null): array
    {
        $return = [];

        foreach ($contacts as $contact) {
            $return[] = $this->contactPoint(
                areas_served: $contact['areasServed'] ?? null,
                email: $contact['email'] ?? null,
                contactPage: $contactPage,
                languages: $contact['languages'] ?? null,
                name: $contact['name'] ?? null,
                open_hours: $contact['openHours'] ?? null,
                options: $contact['options'] ?? null,
                phone: $contact['phone'] ?? null,
                social_links: $contact['socialLinks'] ?? null,
                type: $contact['type'] ?? null,
            );
        }

        return $return;
    }

    protected function getSchemaDay(string $day): string
    {
        return match ($day) {
            // TODO convert to WeekDayEnum
            'monday' => 'https://schema.org/Monday',
            'tuesday' => 'https://schema.org/Tuesday',
            'wednesday' => 'https://schema.org/Wednesday',
            'thursday' => 'https://schema.org/Thursday',
            'friday' => 'https://schema.org/Friday',
            'saturday' => 'https://schema.org/Saturday',
            'sunday' => 'https://schema.org/Sunday',
            'public_holidays' => 'https://schema.org/PublicHolidays',
        };
    }

    protected function getSchemaSpecification(
        array $days,
        ?string $dateFrom,
        ?string $dateUntil,
        ?string $openTime,
        ?string $closeTime,
    ): array {
        $return = [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => [],
        ];

        foreach ($days as $day) {
            $return['dayOfWeek'][] = $this->getSchemaDay($day);
        }

        if (! in_array($dateFrom, [null, '', '0'], true)) {
            $return['validFrom'] = $dateFrom;
        }

        if (! in_array($dateUntil, [null, '', '0'], true)) {
            $return['validThrough'] = $dateUntil;
        }

        if (! in_array($openTime, [null, '', '0'], true)) {
            $return['opens'] = $openTime;
        }

        if (! in_array($closeTime, [null, '', '0'], true)) {
            $return['closes'] = $closeTime;
        }

        return $return;
    }

    protected function mediaItem(Media $media): array
    {
        $return = [
            '@context' => 'https://schema.org',
            '@type' => 'ImageObject',
            'url' => $media->getAvailableUrl([MediaConversionEnum::Large->value]),
            'name' => $media->name,
            'datePublished' => $media->created_at->toDateString(),
        ];

        $caption = $media->getCustomProperty('caption');
        if ($caption !== null && $caption !== '') {
            $return['caption'] = $caption;
        }

        $description = $media->getCustomProperty('description');
        if ($description !== null && $description !== '') {
            $return['description'] = $description;
        }

        return $return;
    }

    /**
     * @param  Collection<int, Media>|null  $media
     */
    protected function process(
        string $type,
        string $name,
        ?string $alternateName = null,
        ?array $areasServed = null,
        ?array $contacts = null,
        ?array $currenciesAccepted = null,
        ?string $description = null,
        ?string $email = null,
        bool|Media|null $logo = null,
        ?Collection $media = null,
        ?array $openHours = null,
        ?array $paymentAccepted = null,
        ?string $phone = null,
        ?string $priceRange = null,
        ?array $socialLinks = null,
    ): array {
        $entityType = SchemaEntityTypeEnum::tryFrom($type) ?? SchemaEntityTypeEnum::Organization;

        $item = [
            '@context' => 'https://schema.org',
            '@type' => $type,
            '@id' => $entityType->toId($this->site_domain),
            'name' => $name,
            'url' => $this->site_domain,
        ];

        if (! in_array($alternateName, [null, '', '0'], true)) {
            $item['alternateName'] = $alternateName;
        }

        if (! in_array($description, [null, '', '0'], true)) {
            $item['translation'] = $description;
        }

        if ($logo !== null) {
            $item['logo'] = $this->mediaItem($logo);
        }

        if (! in_array($email, [null, '', '0'], true)) {
            $item['email'] = $email;
        }

        if (! in_array($phone, [null, '', '0'], true)) {
            $item['telephone'] = $phone;
        }

        if (! in_array($priceRange, [null, '', '0'], true)) {
            $item['priceRange'] = $priceRange;
        }

        if ($paymentAccepted !== null && $paymentAccepted !== []) {
            $item['paymentAccepted'] = $paymentAccepted;
        }

        if ($currenciesAccepted !== null && $currenciesAccepted !== []) {
            $item['currenciesAccepted'] = $currenciesAccepted;
        }

        if ($media instanceof Collection && $media->isNotEmpty()) {
            $firstMedia = $media->first();
            $item['image'] = $this->mediaItem($firstMedia);

            $remaining = $media->slice(1);

            if ($remaining->isNotEmpty()) {
                $item['photos'] = $remaining->map(fn (Media $siteMedia): array => $this->mediaItem($siteMedia))
                    ->values()
                    ->all();
            }
        }

        if ($socialLinks !== null && $socialLinks !== []) {
            $item['sameAs'] = $this->sameAs($socialLinks);
        }

        if ($areasServed !== null && $areasServed !== []) {
            $item['areaServed'] = $this->areasServed($areasServed);
        }

        if ($contacts !== null && $contacts !== []) {
            $contactPage = Page::query()->where('key', 'contact')->first();
            $item['contactPoint'] = $this->contactPoints($contacts, $contactPage);
        }

        if ($openHours !== null && $openHours !== []) {
            $item['openingHoursSpecification'] = $this->openingHoursSpecification($openHours);
        }

        return $item;
    }

    protected function sameAs(array $social_links): array
    {
        $return = [];

        foreach ($social_links as $social_link) {
            $return[] = $social_link['url'];
        }

        return $return;
    }

    private function openingHoursSpecification(array $open_hours): array
    {
        $return = [];

        foreach ($open_hours as $open_hour) {
            $return[] = $this->getSchemaSpecification(
                days: $open_hour['days'],
                dateFrom: $open_hour['date_from'] ?? null,
                dateUntil: $open_hour['date_until'] ?? null,
                openTime: $open_hour['open_time'] ?? null,
                closeTime: $open_hour['close_time'] ?? null,
            );
        }

        return $return;
    }
}
