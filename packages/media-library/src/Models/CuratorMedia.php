<?php

declare(strict_types=1);

namespace Capell\MediaLibrary\Models;

use Awcodes\Curator\Models\Media as BaseCuratorMedia;
use Capell\Core\Contracts\Media\MediaContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Curator's Media model, extended to satisfy Capell's backend-agnostic
 * MediaContract. Subclasses Awcodes\Curator\Models\Media so Curator-native
 * features (picker, library, glide) continue to work untouched.
 *
 * Curator does not implement Spatie-style conversions / responsive images;
 * the corresponding contract methods are implemented to return sensible
 * no-op values. The `$conversion` argument is accepted for contract
 * compliance but has no effect.
 */
final class CuratorMedia extends BaseCuratorMedia implements MediaContract
{
    use HasFactory;

    public function getUrl(string $conversion = ''): string
    {
        // `url` is an Eloquent accessor on BaseCuratorMedia; reading
        // $this->url triggers it and returns a storage-resolved URL.
        return $this->url;
    }

    public function getFullUrl(string $conversion = ''): string
    {
        return $this->getUrl($conversion);
    }

    /**
     * @param  array<int, string>  $conversions
     */
    public function getAvailableFullUrl(array $conversions): string
    {
        return $this->getUrl();
    }

    public function getSrcset(): string
    {
        return '';
    }

    public function hasResponsiveImages(): bool
    {
        return false;
    }

    public function hasConversion(string $conversion): bool
    {
        return false;
    }

    public function getName(): string
    {
        $name = $this->name ?? null;

        if ($name !== null && $name !== '') {
            return $name;
        }

        $prettyName = $this->pretty_name ?? null;

        return $prettyName ?? '';
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMimeType(): string
    {
        return $this->type;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getCustomProperty(string $key, mixed $default = null): mixed
    {
        return match ($key) {
            'alt' => $this->alt ?? $default,
            'title' => $this->title ?? $default,
            'description' => $this->description ?? $default,
            'caption' => $this->caption ?? $default,
            'width' => $this->width ?? $default,
            'height' => $this->height ?? $default,
            default => $default,
        };
    }
}
