<?php

declare(strict_types=1);

namespace Capell\Workspaces\Exceptions;

use Capell\Core\Models\Workspace;
use RuntimeException;

/**
 * Thrown at publish time when promoting workspace-scoped page_urls to live
 * would collide with existing live URLs belonging to records not touched by
 * this workspace. The workspace must be rebased or the conflicting URLs
 * changed before publishing.
 *
 * @property array<int, array{site_id: int, language_id: int, url: string}> $collisions
 */
class UrlCollisionException extends RuntimeException
{
    /** @param  array<int, array{site_id: int, language_id: int, url: string}>  $collisions */
    public function __construct(
        public readonly Workspace $workspace,
        public readonly array $collisions,
    ) {
        $summary = collect($collisions)
            ->take(5)
            ->map(fn (array $row): string => sprintf(
                '  - site %d / lang %d / url %s',
                $row['site_id'],
                $row['language_id'],
                $row['url'],
            ))
            ->implode("\n");

        parent::__construct(sprintf(
            "Publishing workspace #%d \"%s\" would create %d duplicate URL(s) in live:\n%s",
            $workspace->id,
            $workspace->name,
            count($collisions),
            $summary,
        ));
    }
}
