<?php

declare(strict_types=1);

namespace Capell\Redirects\Contracts;

use Capell\Core\Models\PageUrl;

interface RedirectRecorder
{
    public function recordHit(PageUrl $pageUrl): void;
}
