<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Listeners;

use Capell\Core\Events\UrlVisitFailed;
use Capell\SeoSuite\Actions\RecordBrokenLinkAction;

final class RecordBrokenLink
{
    public function handle(UrlVisitFailed $event): void
    {
        RecordBrokenLinkAction::run($event->url, $event->statusCode, $event->pageId);
    }
}
