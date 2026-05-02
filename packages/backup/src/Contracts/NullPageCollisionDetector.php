<?php

declare(strict_types=1);

namespace Capell\Backup\Contracts;

use Capell\Backup\Data\PageReviewRow;

final class NullPageCollisionDetector implements PageCollisionDetector
{
    public function detect(array $urls, ?int $resolvedSiteId): array
    {
        return [PageReviewRow::COLLISION_NONE, [], PageReviewRow::ACTION_CREATE];
    }
}
