<?php

declare(strict_types=1);

use Capell\Core\Enums\MediaConversionEnum;
use Capell\Core\Models\Media;
use Capell\Frontend\Facades\Frontend;

$site = Frontend::site();
$language = Frontend::language();

$page = Frontend::page();

if ($page->image === null && $page->media->isEmpty()) {
    return;
}

$json = [];

if ($page->image !== null || $page->media->isNotEmpty()) {
    $image = null;

    if ($page->image !== null) {
        $image = [
            '@context' => 'https://schema.org',
            '@type' => 'ImageObject',
            'contentUrl' => $page->image->getAvailableUrl([MediaConversionEnum::Large->value]),
            'name' => $page->image->name,
            'datePublished' => $page->image->created_at->toDateString(),
        ];

        if ($page->image->getCustomProperty('caption') !== null && $page->image->getCustomProperty('caption') !== '') {
            $image['caption'] = $page->image->getCustomProperty('caption');
        }

        if ($page->image->getCustomProperty('description') !== null && $page->image->getCustomProperty('description') !== '') {
            $image['description'] = $page->image->getCustomProperty('description');
        }

        $json[] = $image;
    }

    $page->media->each(function (Media $media) use ($page, &$json, $image): void {
        if (is_array($image) && $media->id === $page->image->id) {
            return;
        }

        $mediaImage = [
            '@context' => 'https://schema.org',
            '@type' => 'ImageObject',
            'contentUrl' => $media->getAvailableUrl([MediaConversionEnum::Large->value]),
            'name' => $media->name,
            'datePublished' => $media->created_at->toDateString(),
        ];

        if ($media->getCustomProperty('caption') !== null && $media->getCustomProperty('caption') !== '') {
            $mediaImage['caption'] = $media->getCustomProperty('caption');
        }

        if ($media->getCustomProperty('description') !== null && $media->getCustomProperty('description') !== '') {
            $mediaImage['description'] = $media->getCustomProperty('description');
        }

        $json[] = $mediaImage;
    });
}

?>

{!! '<script type="application/ld+json">' . json_encode($json, JSON_UNESCAPED_SLASHES) . '</script>' !!}
