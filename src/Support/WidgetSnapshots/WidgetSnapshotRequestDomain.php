<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\WidgetSnapshots;

use Capell\Core\Actions\LoadSiteDomainFromUrlAction;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;

final class WidgetSnapshotRequestDomain
{
    public function resolve(int $siteId, int $languageId): ?SiteDomain
    {
        $site = Site::query()->with('siteDomains')->find($siteId);
        if (! $site instanceof Site) {
            return null;
        }

        $resolved = LoadSiteDomainFromUrlAction::run(request()->fullUrl(), collect([$site]));
        $domain = $resolved[0] ?? null;
        if (! $domain instanceof SiteDomain || $domain->site_id !== $siteId || ! $domain->status) {
            return null;
        }

        $boundLanguageId = $domain->language_id ?? $site->language_id;

        return $boundLanguageId === $languageId ? $domain : null;
    }

    public function locatorUrl(SiteDomain $domain, string $locator): string
    {
        $path = trim((string) ($domain->path ?? ''), '/');
        $prefix = $path === '' ? '' : '/' . $path;

        return rtrim(request()->getSchemeAndHttpHost(), '/') . $prefix . '/_capell/layout-widgets/' . rawurlencode($locator);
    }
}
