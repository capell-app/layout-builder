<?php

declare(strict_types=1);

use Capell\Address\Models\Address;
use Capell\Address\Models\Country;

describe('Address model methods', function (): void {
    it('finds address by line1, postal code, and country', function (): void {
        $country = Country::factory()->create();
        $address = Address::factory()->create([
            'line1' => '123 Main St',
            'postal_code' => '12345',
            'country_id' => $country->id,
        ]);

        $found = Address::findAddress('123 Main St', '12345', $country->id);

        expect($found)->not()->toBeNull();
        expect($found->id)->toBe($address->id);
    });

    it('returns null when address not found', function (): void {
        $country = Country::factory()->create();

        $found = Address::findAddress('999 Nonexistent St', '99999', $country->id);

        expect($found)->toBeNull();
    });

    it('finds correct address among multiple similar addresses', function (): void {
        $country = Country::factory()->create();
        Address::factory()->create([
            'line1' => '123 Main St',
            'postal_code' => '12345',
            'country_id' => $country->id,
        ]);
        $target = Address::factory()->create([
            'line1' => '123 Main St',
            'postal_code' => '54321',
            'country_id' => $country->id,
        ]);

        $found = Address::findAddress('123 Main St', '54321', $country->id);

        expect($found->id)->toBe($target->id);
    });
});
