<?php

declare(strict_types=1);

use Capell\Address\Filament\Components\Forms\AddressSelect;
use Capell\Address\Models\Address;

it('searches addresses by persisted address line columns', function (): void {
    $matchingAddress = Address::factory()->create([
        'name' => 'Warehouse Address',
        'line1' => '10 Foundry Street',
        'line2' => 'Suite 500',
    ]);

    $otherAddress = Address::factory()->create([
        'name' => 'Office Address',
        'line1' => '99 Market Road',
        'line2' => 'Floor 2',
    ]);

    $results = AddressSelect::make('address_id')->getSearchResults('Suite 500');

    expect($results)
        ->toHaveKey($matchingAddress->getKey())
        ->not->toHaveKey($otherAddress->getKey());
});
