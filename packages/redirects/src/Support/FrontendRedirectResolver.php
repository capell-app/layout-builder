<?php

declare(strict_types=1);

namespace Capell\Redirects\Support;

use Capell\Core\Models\Language;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Frontend\Contracts\RedirectResolver;
use Capell\Frontend\Data\RedirectDecisionData;

class FrontendRedirectResolver implements RedirectResolver
{
    public function __construct(
        private readonly PageUrlRedirectResolver $redirectResolver,
    ) {}

    public function resolve(Site $site, Language $language, string $url, ?int $pageId = null, ?PageUrl $pageUrl = null): ?RedirectDecisionData
    {
        $decision = $this->redirectResolver->resolve($site, $language, $url, $pageId, $pageUrl);

        if (! $decision instanceof \Capell\Redirects\Data\RedirectDecisionData) {
            return null;
        }

        return new RedirectDecisionData($decision->targetUrl, $decision->statusCode);
    }
}
