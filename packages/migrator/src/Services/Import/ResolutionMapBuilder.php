<?php

declare(strict_types=1);

namespace Capell\Migrator\Services\Import;

use Capell\Migrator\Services\Import\Resolvers\MatchResolution;
use Capell\Migrator\Services\Import\Resolvers\MatchResolver;
use Capell\Migrator\Services\Import\Resolvers\RelationMatchResolverRegistry;
use RuntimeException;

/**
 * Walks the shared-relation descriptors in a read package payload and asks
 * each registered resolver to find a local match. Returns a ResolutionMap
 * describing every ref plus the list of refs that still need a human.
 *
 * Accepts either a RelationMatchResolverRegistry (preferred; lets packages
 * extend the priority chain) or the legacy array<group, MatchResolver>
 * shape for BC with existing call sites and tests.
 */
final readonly class ResolutionMapBuilder
{
    private RelationMatchResolverRegistry $registry;

    /**
     * @param  RelationMatchResolverRegistry|array<string, MatchResolver|list<MatchResolver>>  $resolvers
     */
    public function __construct(RelationMatchResolverRegistry|array $resolvers)
    {
        $this->registry = $resolvers instanceof RelationMatchResolverRegistry
            ? $resolvers
            : new RelationMatchResolverRegistry($resolvers);
    }

    /**
     * @param  array<string, string>  $payload  archive-path => JSON contents
     */
    public function build(array $payload): ResolutionMap
    {
        $resolved = [];
        $unresolved = [];

        foreach ($payload as $entryPath => $contents) {
            if (! str_starts_with($entryPath, 'relations/')) {
                continue;
            }

            $parts = explode('/', $entryPath, 3);
            if (count($parts) < 3) {
                continue;
            }

            $folder = $parts[1];

            $descriptor = $this->decode($contents, $entryPath);
            $ref = is_string($descriptor['ref'] ?? null) ? $descriptor['ref'] : null;
            if ($ref === null) {
                continue;
            }

            if (! $this->registry->hasGroup($folder)) {
                $unresolved[] = $ref;

                continue;
            }

            $resolution = $this->registry->resolve($folder, $descriptor);
            if (! $resolution instanceof MatchResolution) {
                $unresolved[] = $ref;

                continue;
            }

            $resolved[$ref] = $resolution;
        }

        return new ResolutionMap(resolved: $resolved, unresolved: $unresolved);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $contents, string $entryPath): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if ($decoded === []) {
            throw new RuntimeException(sprintf('Empty descriptor for [%s].', $entryPath));
        }

        return $decoded;
    }
}
