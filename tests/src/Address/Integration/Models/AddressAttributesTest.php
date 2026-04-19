<?php

declare(strict_types=1);

use Capell\Address\Models\Address;
use Capell\Address\Models\Country;

describe('Address model attributes', function (): void {
    it('generates full address from all parts', function (): void {
        $country = Country::factory()->create(['name' => 'United States']);
        $address = Address::factory()->create([
            'line1' => '123 Main St',
            'line2' => 'Suite 100',
            'city' => 'Boston',
            'state' => 'MA',
            'postal_code' => '02101',
            'country_id' => $country->id,
        ]);

        expect($address->full_address)->toBe(
            '123 Main St, Suite 100, Boston, MA, 02101, United States',
        );
    });

    it('generates full address without optional parts', function (): void {
        $country = Country::factory()->create(['name' => 'Canada']);
        $address = Address::factory()->create([
            'line1' => '456 Oak Ave',
            'line2' => null,
            'city' => 'Toronto',
            'state' => 'ON',
            'postal_code' => 'M5V 3A8',
            'country_id' => $country->id,
        ]);

        expect($address->full_address)->toBe(
            '456 Oak Ave, Toronto, ON, M5V 3A8, Canada',
        );
    });

    it('generates full address with only required parts', function (): void {
        $address = new Address([
            'line1' => '789 Elm St',
            'line2' => null,
            'city' => null,
            'state' => null,
            'postal_code' => null,
            'country_id' => null,
        ]);

        expect($address->full_address)->toBe('789 Elm St');
    });

    it('generates full address excluding null country', function (): void {
        $address = Address::factory()->create([
            'line1' => '100 Pine Rd',
            'line2' => null,
            'city' => 'Springfield',
            'state' => null,
            'postal_code' => '62701',
            'country_id' => null,
        ]);

        expect($address->full_address)->toBe('100 Pine Rd, Springfield, 62701');
    });
});
