<?php

declare(strict_types=1);

namespace Capell\DefaultTheme\Support\Media;

use Capell\Frontend\Support\State\FrontendState;
use DateTimeInterface;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\Support\UrlGenerator\BaseUrlGenerator;

class CapellUrlGenerator extends BaseUrlGenerator
{
    public function getUrl(): string
    {
        $url = $this->getDisk()->url($this->getPathRelativeToRoot());

        $url = $this->maybePrefixWithSiteBaseUrl($url);

        return $this->versionUrl($url);
    }

    public function getTemporaryUrl(DateTimeInterface $expiration, array $options = []): string
    {
        $tmp = $this->getDisk()->temporaryUrl($this->getPathRelativeToRoot(), $expiration, $options);

        return $this->maybePrefixWithSiteBaseUrl($tmp);
    }

    public function getPath(): string
    {
        return $this->getRootOfDisk() . $this->getPathRelativeToRoot();
    }

    public function getResponsiveImagesDirectoryUrl(): string
    {
        $path = $this->pathGenerator->getPathForResponsiveImages($this->media);

        $url = $this->getDisk()->url($path);

        return Str::finish($this->maybePrefixWithSiteBaseUrl($url), '/');
    }

    protected function getRootOfDisk(): string
    {
        return $this->getDisk()->path('/');
    }

    private function maybePrefixWithSiteBaseUrl(string $url): string
    {
        $activeRoot = resolve(FrontendState::class)->rootUrl();
        if (is_string($activeRoot) && $activeRoot !== '') {
            return $this->rewriteOrigin($url, $activeRoot);
        }

        $useSiteDomain = config('capell-default-theme.use_site_domain_for_media', false);
        if ($useSiteDomain === false) {
            return $url;
        }

        $overrideBase = config('capell-default-theme.local_storage_url', '');
        $siteBase = config('capell-default-theme.site_base_url', '');
        $base = $overrideBase !== '' ? $overrideBase : $siteBase;

        if ($base === '') {
            return $url;
        }

        return $this->rewriteOrigin($url, $base);
    }

    private function rewriteOrigin(string $url, string $base): string
    {
        $base = rtrim($base, '/');

        if (Str::startsWith($url, ['http://', 'https://'])) {
            $parsed = parse_url($url);
            $path = is_array($parsed) && isset($parsed['path']) ? $parsed['path'] : '';
            $query = is_array($parsed) && isset($parsed['query']) && $parsed['query'] !== '' ? '?' . $parsed['query'] : '';

            return $base . $path . $query;
        }

        return $base . '/' . ltrim($url, '/');
    }
}
