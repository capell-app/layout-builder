<?php

declare(strict_types=1);

namespace Capell\Migrator\Services\Import\Resolvers;

/**
 * Given a shared-relation descriptor from an incoming package, return
 * the ID of the best matching local record or null if none was found.
 *
 * Resolvers walk a strict preference order (uuid → key/slug → normalised
 * name → fingerprint) so that round-trip imports stay stable across
 * environments even when primary keys drift.
 */
interface MatchResolver
{
    /**
     * @param  array<string, mixed>  $descriptor  decoded relations/<folder>/<key>.json
     */
    public function resolve(array $descriptor): ?MatchResolution;
}
