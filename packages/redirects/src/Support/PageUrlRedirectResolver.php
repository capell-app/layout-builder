<?php

declare(strict_types=1);

namespace Capell\Redirects\Support;

use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Redirects\Contracts\RedirectRecorder;
use Capell\Redirects\Contracts\RedirectResolver;
use Capell\Redirects\Data\RedirectDecisionData;
use Illuminate\Database\Eloquent\Builder;

class PageUrlRedirectResolver implements RedirectResolver
{
    public function __construct(
        private readonly RedirectRecorder $redirectRecorder,
    ) {}

    public function resolve(Site $site, Language $language, string $url, ?int $pageId = null, ?PageUrl $pageUrl = null): ?RedirectDecisionData
    {
        $pageUrl ??= $this->findPageUrl($site, $language, $url, $pageId);

        if (! $pageUrl instanceof PageUrl || ! $pageUrl->isRedirect()) {
            return null;
        }

        $this->redirectRecorder->recordHit($pageUrl);

        if ($pageUrl->hasTargetUrl()) {
            return new RedirectDecisionData(
                targetUrl: $pageUrl->target_url,
                statusCode: $pageUrl->status_code?->value ?? 301,
            );
        }

        $targetPageUrl = $this->findCurrentPageUrl($site, $language, $pageUrl);

        if (! $targetPageUrl instanceof PageUrl) {
            return null;
        }

        return new RedirectDecisionData(targetUrl: $targetPageUrl->url, statusCode: 301);
    }

    private function findPageUrl(Site $site, Language $language, string $url, ?int $pageId = null): ?PageUrl
    {
        return PageUrl::query()
            ->where('site_id', $site->getKey())
            ->where('language_id', $language->getKey())
            ->where('url', $url)
            ->where('status', true)
            ->when($pageId, fn (Builder $query): Builder => $query->where('pageable_id', $pageId))
            ->first();
    }

    private function findCurrentPageUrl(Site $site, Language $language, PageUrl $redirectPageUrl): ?PageUrl
    {
        return PageUrl::query()
            ->where('site_id', $site->getKey())
            ->where('language_id', $language->getKey())
            ->where('pageable_type', $redirectPageUrl->pageable_type)
            ->where('pageable_id', $redirectPageUrl->pageable_id)
            ->where('status', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('type')
                    ->orWhere('type', '!=', UrlTypeEnum::Redirect);
            })
            ->first();
    }
}
