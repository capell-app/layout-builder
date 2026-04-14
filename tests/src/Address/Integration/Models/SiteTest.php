<?php

declare(strict_types=1);

// tests/Integration/Models/SiteTest.php

use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Capell\Core\Models\Site;

it('can relate to an address', function (): void {
    $address = Address::factory()->create(['name' => 'Headquarters']);
    $site = Site::factory()
        ->state(fn (array $attributes): array => [
            'meta' => array_merge($attributes['meta'] ?? [], ['address_id' => $address->getKey()]),
        ])
        ->create();

    expect($site->address)
        ->toBeInstanceOf(Address::class)
        ->id->toBe($address->id);
});

it('can relate to a country via address', function (): void {
    $country = Country::factory()->create(['name' => 'Testland']);
    $address = Address::factory()->create(['country_id' => $country->id]);
    $site = Site::factory()
        ->state(fn (array $attributes): array => [
            'meta' => array_merge($attributes['meta'] ?? [], ['address_id' => $address->getKey()]),
        ])
        ->create();

    expect($site->address->country)
        ->toBeInstanceOf(Country::class)
        ->and($site->address->country->id)->toBe($country->id);
});
