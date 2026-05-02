<?php

declare(strict_types=1);

namespace Capell\Redirects\Contracts;

use Capell\Core\Models\Language;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Redirects\Data\RedirectDecisionData;

class NullRedirectResolver implements RedirectResolver
{
    public function resolve(Site $site, Language $language, string $url, ?int $pageId = null, ?PageUrl $pageUrl = null): ?RedirectDecisionData
    {
        return null;
    }
}
