<?php

declare(strict_types=1);

namespace Capell\Migrator\Contracts;

/**
 * Detects whether an incoming import URL collides with existing data.
 *
 * Core provides a null implementation that reports no collisions. Packages
 * that want richer behavior bind their own implementation over the container
 * key.
 */
interface PageCollisionDetector
{
    /**
     * @param  list<array{site_id: int|null, language_id: int|null, url: string}>  $urls
     * @return array{0: string, 1: list<string>, 2: string} [collisionState, conflictMessages, suggestedAction]
     */
    public function detect(array $urls, ?int $resolvedSiteId): array;
}
