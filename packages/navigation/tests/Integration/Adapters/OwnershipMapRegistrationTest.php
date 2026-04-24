<?php

declare(strict_types=1);

use Capell\Core\Exchanger\Enums\RelationOwnership;
use Capell\Core\Exchanger\Policy\OwnershipMap;
use Capell\Navigation\Models\Navigation;

it('registers Navigation in OwnershipMap as shared when the package boots', function (): void {
    expect(OwnershipMap::for(Navigation::class))->toBe(RelationOwnership::Shared);
});

it('exposes Navigation in OwnershipMap::all()', function (): void {
    expect(OwnershipMap::all())->toHaveKey(Navigation::class);
});
