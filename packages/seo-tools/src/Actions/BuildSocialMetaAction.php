<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\MediaConversionEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\SeoTools\Data\SocialMetaData;
use Capell\SeoTools\Enums\OpenGraphTypeEnum;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @method static SocialMetaData run(Pageable $page, Site $site, Language $language)
 */
class BuildSocialMetaAction
{
    use AsAction;

    public function handle(Pageable $page, Site $site, Language $language): SocialMetaData
    {
        $schemaType = $page->type?->meta['schema']['type'] ?? null;
        $ogType = OpenGraphTypeEnum::fromSchemaType($schemaType);

        $socialTitle = $this->resolveSocialTitle($page, $site);
        $socialDescription = $this->resolveSocialDescription($page);

        $image = $this->resolveSocialImage($page, $site);
        $imageUrl = $image?->getAvailableUrl([MediaConversionEnum::Large->value]);
        $imageWidth = $image instanceof Media ? $this->getImageWidth($image) : null;
        $imageHeight = $image instanceof Media ? $this->getImageHeight($image) : null;
        $imageMimeType = $image?->mime_type;
        $imageAlt = $image?->getCustomProperty('alt') ?? $image?->getCustomProperty('caption') ?? $image?->name;

        return new SocialMetaData(
            title: $socialTitle,
            description: $socialDescription,
            imageUrl: $imageUrl,
            imageWidth: $imageWidth,
            imageHeight: $imageHeight,
            imageMimeType: $imageMimeType,
            imageAlt: $imageAlt,
            ogType: $ogType,
            url: $page->pageUrl?->full_url ?? '',
            locale: app()->getLocale(),
            siteName: $site->translation?->title !== null && $site->translation?->title !== '' ? strip_tags($site->translation->title) : null,
            twitterHandle: $site->getMeta('twitter'),
            articlePublishedTime: $ogType->isArticle() ? ($page->visible_from ?? $page->created_at)?->toIso8601String() : null,
            articleModifiedTime: $ogType->isArticle() ? $page->updated_at?->toIso8601String() : null,
            articleAuthor: $ogType->isArticle() ? data_get($page, 'creator.name') : null,
        );
    }

    private function resolveSocialTitle(Pageable $page, Site $site): string
    {
        $translation = $page->translation;

        $socialTitle = $translation->getMeta('social_title');
        if ($socialTitle !== null && $socialTitle !== '') {
            return $socialTitle;
        }

        if ($translation->meta_title !== null && $translation->meta_title !== '') {
            return $translation->meta_title;
        }

        $title = $translation->title ?? '';
        $append = $site->translation?->getMeta('title_after_text', $site->translation->title);

        if (! in_array($append, [null, '', $title], true)) {
            $title .= config('capell-frontend.meta_title_seperator', ' ') . $append;
        }

        return $title;
    }

    private function resolveSocialDescription(Pageable $page): string
    {
        $translation = $page->translation;

        $socialDescription = $translation->getMeta('social_description');
        if ($socialDescription !== null && $socialDescription !== '') {
            return strip_tags((string) $socialDescription);
        }

        return strip_tags($translation->meta_description ?? '');
    }

    private function resolveSocialImage(Pageable $page, Site $site): ?Media
    {
        $socialImage = method_exists($page, 'socialImage') ? $page->socialImage : null;

        if ($socialImage instanceof Media) {
            return $socialImage;
        }

        $image = method_exists($page, 'image') ? $page->image : null;

        if ($image instanceof Media) {
            return $image;
        }

        return $site->image;
    }

    private function getImageWidth(Media $media): ?int
    {
        $width = $media->getCustomProperty('width');

        return $width !== null ? (int) $width : null;
    }

    private function getImageHeight(Media $media): ?int
    {
        $height = $media->getCustomProperty('height');

        return $height !== null ? (int) $height : null;
    }
}
