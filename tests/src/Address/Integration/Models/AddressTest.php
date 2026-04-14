<?php

declare(strict_types=1);

use Capell\Address\Models\Address;
use Capell\Address\Models\Country;

describe('Address model', function (): void {
    it('can create an address with attributes', function (): void {
        $address = Address::factory()->create([
            'name' => 'John Doe',
            'line1' => '123 Main St',
            'city' => 'Testville',
            'postal_code' => '12345',
        ]);

        expect($address)
            ->name->toBe('John Doe')
            ->line1->toBe('123 Main St')
            ->city->toBe('Testville')
            ->postal_code->toBe('12345');
    });

    it('can relate to a country', function (): void {
        $country = Country::factory()->create(['name' => 'Testland']);
        $address = Address::factory()->create(['country_id' => $country->id]);
        expect($address->country)->not()->toBeNull();
        expect($address->country->name)->toBe('Testland');
    });

    it('casts meta to json and stores coordinates', function (): void {
        $address = Address::factory()->create([
            'meta' => [
                'latitude' => 12.34,
                'longitude' => 56.78,
            ],
        ]);
        expect($address->meta)->toBeArray();
        expect($address->meta['latitude'])->toBe(12.34);
        expect($address->meta['longitude'])->toBe(56.78);
    });
});
