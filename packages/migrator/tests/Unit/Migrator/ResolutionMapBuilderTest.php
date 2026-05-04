<?php

declare(strict_types=1);

use Capell\Migrator\Services\Import\ResolutionMapBuilder;
use Capell\Migrator\Services\Import\Resolvers\MatchResolution;
use Capell\Migrator\Services\Import\Resolvers\MatchResolver;

function makeResolver(?MatchResolution $resolution): MatchResolver
{
    return new class($resolution) implements MatchResolver
    {
        public function __construct(private readonly ?MatchResolution $resolution) {}

        public function resolve(array $descriptor): ?MatchResolution
        {
            return $this->resolution;
        }
    };
}

it('resolves refs that a matching resolver handles', function (): void {
    $builder = new ResolutionMapBuilder([
        'layouts' => makeResolver(new MatchResolution(localId: 42, strategy: 'key')),
    ]);

    $map = $builder->build([
        'relations/layouts/abc.json' => json_encode(['type' => 'layout', 'ref' => 'layout:1', 'key' => 'home']),
    ]);

    expect($map->hasUnresolved())->toBeFalse()
        ->and($map->localIdFor('layout:1'))->toBe(42);
});

it('records refs with no matching resolver as unresolved', function (): void {
    $builder = new ResolutionMapBuilder(resolvers: []);

    $map = $builder->build([
        'relations/layouts/abc.json' => json_encode(['type' => 'layout', 'ref' => 'layout:1']),
    ]);

    expect($map->hasUnresolved())->toBeTrue()
        ->and($map->unresolved)->toBe(['layout:1']);
});

it('records refs the resolver rejects as unresolved', function (): void {
    $builder = new ResolutionMapBuilder([
        'layouts' => makeResolver(null),
    ]);

    $map = $builder->build([
        'relations/layouts/abc.json' => json_encode(['type' => 'layout', 'ref' => 'layout:1']),
    ]);

    expect($map->unresolved)->toBe(['layout:1']);
});

it('skips non-relation entries', function (): void {
    $builder = new ResolutionMapBuilder([
        'layouts' => makeResolver(new MatchResolution(localId: 1, strategy: 'key')),
    ]);

    $map = $builder->build([
        'pages/p.json' => json_encode(['type' => 'page']),
    ]);

    expect($map->resolved)->toBe([])
        ->and($map->unresolved)->toBe([]);
});
