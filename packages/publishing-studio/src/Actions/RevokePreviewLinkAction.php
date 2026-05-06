<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions;

use Capell\PublishingStudio\Models\PreviewLink;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Date;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Marks a preview link as revoked so the workspace preview URL it backs is
 * no longer usable, even if the signed URL itself has not yet expired.
 */
class RevokePreviewLinkAction
{
    use AsObject;

    public function handle(PreviewLink $link, Authenticatable $actor): PreviewLink
    {
        $link->revoked_at = Date::now();
        $link->save();

        return $link;
    }
}
