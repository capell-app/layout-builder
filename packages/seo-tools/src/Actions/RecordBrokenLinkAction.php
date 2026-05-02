<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\SeoTools\Models\BrokenLink;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordBrokenLinkAction
{
    use AsAction;

    public function handle(string $url, int $statusCode, ?int $pageId): void
    {
        if ($pageId === null) {
            return;
        }

        BrokenLink::query()->updateOrCreate(
            [
                'page_id' => $pageId,
                'target_url' => $url,
            ],
            [
                'http_status' => $statusCode,
                'last_checked_at' => now(),
            ],
        );
    }
}
