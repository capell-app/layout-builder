<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions;

use Capell\PublishingStudio\Models\PreviewLink;
use Illuminate\Contracts\Auth\Authenticatable;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Extends a preview link's expiry by adding extra minutes to the current
 * expires_at timestamp. The token is never changed — existing URLs continue
 * to work for the extended window without being reissued.
 */
class ExtendPreviewLinkAction
{
    use AsObject;

    public function handle(PreviewLink $link, int $extraMinutes, Authenticatable $actor): PreviewLink
    {
        $link->expires_at = $link->expires_at->addMinutes($extraMinutes);
        $link->save();

        return $link;
    }
}
