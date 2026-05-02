<?php

declare(strict_types=1);

namespace Capell\Redirects\Support;

use Capell\Admin\Contracts\Redirects\RedirectUrlRecorder;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Redirects\Actions\AddRedirectUrlAction;

class AdminRedirectUrlRecorder implements RedirectUrlRecorder
{
    public function record(Pageable $pageable, Language $language, string $url): void
    {
        AddRedirectUrlAction::run($pageable, $language, $url);
    }
}
