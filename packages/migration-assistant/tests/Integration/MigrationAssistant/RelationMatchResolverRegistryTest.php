<?php

declare(strict_types=1);

use Capell\MigrationAssistant\Services\Import\Resolvers\MatchResolution;
use Capell\MigrationAssistant\Services\Import\Resolvers\MatchResolver;
use Capell\MigrationAssistant\Services\Import\Resolvers\RelationMatchResolverRegistry;

function stubResolverForRegistryTest(?MatchResolution $result): MatchResolver
{
    return new class($result) implements MatchResolver
    {
        public function __construct(private readonly ?MatchResolution $result) {}

        public function resolve(array $descriptor): ?MatchResolution
        {
            return $this->result;
        }
    };
}

it('returns empty chain for an unknown group', function (): void {
    $registry = new RelationMatchResolverRegistry;

    expect($registry->forGroup('layouts'))->toBe([])
        ->and($registry->hasGroup('layouts'))->toBeFalse()
        ->and($registry->resolve('layouts', []))->toBeNull();
});

it('registers a resolver and makes it available to the chain', function (): void {
    $registry = new RelationMatchResolverRegistry;
    $registry->register('layouts', stubResolverForRegistryTest(
        new MatchResolution(localId: 1, strategy: 'key'),
    ));

    $match = $registry->resolve('layouts', ['ref' => 'layout:1']);

    expect($match)->not->toBeNull()
        ->and($match->localId)->toBe(1)
        ->and($match->strategy)->toBe('key');
});

it('picks the highest-confidence match and exposes the rest as alternatives', function (): void {
    $registry = new RelationMatchResolverRegistry;
    $registry->register('layouts', stubResolverForRegistryTest(
        new MatchResolution(localId: 1, strategy: 'fingerprint', confidence: 0.7),
    ));
    $registry->register('layouts', stubResolverForRegistryTest(
        new MatchResolution(localId: 2, strategy: 'key', confidence: 1.0),
    ));

    $match = $registry->resolve('layouts', []);

    expect($match)->not->toBeNull()
        ->and($match->localId)->toBe(2)
        ->and($match->strategy)->toBe('key')
        ->and($match->alternatives)->toHaveCount(1)
        ->and($match->alternatives[0]->localId)->toBe(1)
        ->and($match->alternatives[0]->strategy)->toBe('fingerprint');
});

it('prepend places a resolver at the front of the chain', function (): void {
    $registry = new RelationMatchResolverRegistry;
    $registry->register('layouts', stubResolverForRegistryTest(
        new MatchResolution(localId: 1, strategy: 'original'),
    ));
    $registry->prepend('layouts', stubResolverForRegistryTest(
        new MatchResolution(localId: 2, strategy: 'new-first'),
    ));

    expect($registry->forGroup('layouts'))->toHaveCount(2)
        ->and($registry->forGroup('layouts')[0]->resolve([])?->strategy)->toBe('new-first');
});

it('skips resolvers that return null and uses the next in the chain', function (): void {
    $registry = new RelationMatchResolverRegistry;
    $registry->register('layouts', stubResolverForRegistryTest(null));
    $registry->register('layouts', stubResolverForRegistryTest(
        new MatchResolution(localId: 99, strategy: 'fallback'),
    ));

    $match = $registry->resolve('layouts', []);

    expect($match?->localId)->toBe(99)
        ->and($match?->strategy)->toBe('fallback');
});
