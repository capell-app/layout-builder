<?php

declare(strict_types=1);

namespace Capell\Backup\Services\Import\Resolvers;

/**
 * Holds a map of relation-group (e.g. "layouts", "types") to an ordered
 * priority chain of resolvers. The first resolver registered for a group
 * runs first; if it returns null the next is tried, and so on.
 *
 * Packages can extend the chain from their own service providers via
 * $registry->register('layouts', new MyCustomResolver), which appends to
 * the end of that group. Use prepend() to place a resolver ahead of the
 * defaults.
 *
 * The ResolutionMapBuilder accepts either this registry or the legacy
 * array<group, MatchResolver> shape, so existing call sites and tests
 * keep working.
 */
final class RelationMatchResolverRegistry
{
    /**
     * @var array<string, list<MatchResolver>>
     */
    private array $resolvers = [];

    /**
     * @param  array<string, MatchResolver|list<MatchResolver>>  $initial
     */
    public function __construct(array $initial = [])
    {
        foreach ($initial as $group => $resolverOrList) {
            if ($resolverOrList instanceof MatchResolver) {
                $this->register($group, $resolverOrList);

                continue;
            }

            foreach ($resolverOrList as $resolver) {
                $this->register($group, $resolver);
            }
        }
    }

    /**
     * Append a resolver to the priority chain for $group.
     */
    public function register(string $group, MatchResolver $resolver): self
    {
        $this->resolvers[$group] ??= [];
        $this->resolvers[$group][] = $resolver;

        return $this;
    }

    /**
     * Place a resolver at the front of the chain for $group — it will be
     * consulted before any resolver that was registered earlier.
     */
    public function prepend(string $group, MatchResolver $resolver): self
    {
        $this->resolvers[$group] ??= [];
        array_unshift($this->resolvers[$group], $resolver);

        return $this;
    }

    /**
     * Replace all resolvers for $group.
     */
    public function set(string $group, MatchResolver $resolver): self
    {
        $this->resolvers[$group] = [$resolver];

        return $this;
    }

    /**
     * @return list<MatchResolver>
     */
    public function forGroup(string $group): array
    {
        return $this->resolvers[$group] ?? [];
    }

    /**
     * @return array<string, list<MatchResolver>>
     */
    public function all(): array
    {
        return $this->resolvers;
    }

    public function hasGroup(string $group): bool
    {
        return isset($this->resolvers[$group]) && $this->resolvers[$group] !== [];
    }

    /**
     * Walk the priority chain for a group and return the best match (plus
     * any lower-confidence alternatives the chain found).
     *
     * @param  array<string, mixed>  $descriptor
     */
    public function resolve(string $group, array $descriptor): ?MatchResolution
    {
        $matches = [];
        foreach ($this->forGroup($group) as $resolver) {
            $match = $resolver->resolve($descriptor);
            if ($match !== null) {
                $matches[] = $match;
            }
        }

        if ($matches === []) {
            return null;
        }

        usort($matches, static fn (MatchResolution $left, MatchResolution $right): int => $right->confidence <=> $left->confidence);

        $best = $matches[0];
        $alternatives = array_slice($matches, 1);

        return $alternatives === [] ? $best : $best->withAlternatives(array_values($alternatives));
    }
}
