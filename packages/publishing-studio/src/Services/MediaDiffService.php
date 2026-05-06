<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Services;

use Intervention\Image\ImageManager;
use Throwable;

class MediaDiffService
{
    /**
     * Produce a MediaDiffResult for two raw attribute values. If intervention/image
     * is available and both values resolve to image paths/URLs, a perceptual-hash
     * comparison is attempted; otherwise falls back to byte equality.
     */
    public function compare(mixed $before, mixed $after): MediaDiffResult
    {
        $beforeUrl = $this->toUrl($before);
        $afterUrl = $this->toUrl($after);
        $contentChanged = $before !== $after;

        $delta = $this->tryPerceptualHash($beforeUrl, $afterUrl);

        return new MediaDiffResult(
            beforeUrl: $beforeUrl,
            afterUrl: $afterUrl,
            perceptualHashDelta: $delta,
            contentChanged: $contentChanged,
        );
    }

    /**
     * Return true when the attribute value looks like it references a media
     * file that can be visually diffed (image extension or URL with image ext).
     */
    public function looksLikeMedia(mixed $value): bool
    {
        if (! is_string($value) || $value === '') {
            return false;
        }

        return (bool) preg_match('/\.(jpe?g|png|gif|webp|avif|svg)(\?.*)?$/i', $value);
    }

    private function toUrl(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    private function tryPerceptualHash(?string $beforeUrl, ?string $afterUrl): ?float
    {
        if ($beforeUrl === null || $afterUrl === null) {
            return null;
        }

        // intervention/image is optional — the diff pipeline works without it.
        if (! class_exists(ImageManager::class)) {
            return null;
        }

        try {
            $manager = ImageManager::gd();
            $hashA = $manager->read($beforeUrl)->greyscale()->resize(8, 8)->toJpeg()->toString();
            $hashB = $manager->read($afterUrl)->greyscale()->resize(8, 8)->toJpeg()->toString();

            return $hashA === $hashB ? 0.0 : 1.0;
        } catch (Throwable) {
            return null;
        }
    }
}
