<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\MigrationAssistant\Enums\RelationOwnership;
use Capell\MigrationAssistant\Policy\OwnershipMap;

afterEach(function (): void {
    OwnershipMap::reset();
});

it('classifies built-in models out of the box', function (): void {
    expect(OwnershipMap::isOwned(PageUrl::class))->toBeTrue()
        ->and(OwnershipMap::isShared(Layout::class))->toBeTrue()
        ->and(OwnershipMap::for(Page::class))->toBe(RelationOwnership::Shared);
});

it('throws when asked about an unregistered model', function (): void {
    expect(fn (): RelationOwnership => OwnershipMap::for('App\\Unknown\\Model'))
        ->toThrow(RuntimeException::class);
});

it('allows packages to register their own ownership rules', function (): void {
    OwnershipMap::register('App\\Package\\Article', RelationOwnership::Owned);

    expect(OwnershipMap::for('App\\Package\\Article'))->toBe(RelationOwnership::Owned);
});

it('lets overrides win over defaults', function (): void {
    OwnershipMap::register(Layout::class, RelationOwnership::Owned);

    expect(OwnershipMap::isOwned(Layout::class))->toBeTrue();
});
