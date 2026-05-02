<?php

declare(strict_types=1);

namespace Capell\Redirects\Support;

use Capell\Core\Models\PageUrl;
use Capell\Redirects\Contracts\RedirectRecorder;

class PageUrlRedirectRecorder implements RedirectRecorder
{
    public function recordHit(PageUrl $pageUrl): void
    {
        $pageUrl->incrementHit();
    }
}
