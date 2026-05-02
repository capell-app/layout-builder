<?php

declare(strict_types=1);

use Capell\Address\Models\Address;

describe('AddressObserver', function (): void {
    it('sets first address as default on creation', function (): void {
        Address::query()->delete();

        $address = Address::factory()->create(['default' => false]);

        expect($address->default)->toBeTrue();
    });

    it('does not override explicit default value if address exists', function (): void {
        Address::factory()->create(['default' => true]);

        $address = Address::factory()->create(['default' => false]);

        expect($address->default)->toBeFalse();
    });

    it('respects explicit true default for subsequent addresses', function (): void {
        Address::query()->delete();
        Address::factory()->create(['default' => true]);

        $address = Address::factory()->create(['default' => true]);

        expect($address->default)->toBeTrue();
    });
});
