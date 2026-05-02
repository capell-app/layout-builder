<?php

declare(strict_types=1);

namespace Capell\Redirects\Contracts;

use Capell\Core\Models\PageUrl;

class NullRedirectRecorder implements RedirectRecorder
{
    public function recordHit(PageUrl $pageUrl): void {}
}
