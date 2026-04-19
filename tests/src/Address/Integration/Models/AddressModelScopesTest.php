<?php

declare(strict_types=1);

use Capell\Address\Models\Address;

describe('Address model scopes', function (): void {
    beforeEach(function (): void {
        Address::query()->delete();
    });

    it('can query default addresses', function (): void {
        Address::factory()->create(['default' => true]);
        Address::factory()->create(['default' => false]);
        Address::factory()->create(['default' => true]);

        $defaults = Address::default()->get();

        expect($defaults)->toHaveCount(2);
        expect($defaults->every(fn (Address $a) => $a->default))->toBeTrue();
    });

    it('can query non-default addresses', function (): void {
        Address::factory()->create(['default' => true]);
        Address::factory()->create(['default' => false]);
        Address::factory()->create(['default' => false]);

        $nonDefaults = Address::nonDefault()->get();

        expect($nonDefaults)->toHaveCount(2);
        expect($nonDefaults->every(fn (Address $a) => ! $a->default))->toBeTrue();
    });

    it('can query enabled addresses', function (): void {
        Address::factory()->create(['status' => true]);
        Address::factory()->create(['status' => false]);
        Address::factory()->create(['status' => true]);

        $enabled = Address::enabled()->get();

        expect($enabled)->toHaveCount(2);
        expect($enabled->every(fn (Address $a) => $a->status))->toBeTrue();
    });

    it('can query disabled addresses', function (): void {
        Address::factory()->create(['status' => true]);
        Address::factory()->create(['status' => false]);
        Address::factory()->create(['status' => false]);

        $disabled = Address::disabled()->get();

        expect($disabled)->toHaveCount(2);
        expect($disabled->every(fn (Address $a) => ! $a->status))->toBeTrue();
    });

    it('can query addresses by status', function (): void {
        Address::factory()->create(['status' => true]);
        Address::factory()->create(['status' => false]);
        Address::factory()->create(['status' => true]);

        $statusTrue = Address::status(true)->get();

        expect($statusTrue)->toHaveCount(2);
    });

    it('returns ordered addresses', function (): void {
        Address::factory()->create([
            'line1' => 'Zebra Lane',
            'line2' => null,
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '78701',
            'country_id' => null,
        ]);
        Address::factory()->create([
            'line1' => 'Apple Ave',
            'line2' => null,
            'city' => 'Boston',
            'state' => 'MA',
            'postal_code' => '02101',
            'country_id' => null,
        ]);

        $ordered = Address::ordered()->get();

        expect($ordered->first()->line1)->toBe('Apple Ave');
        expect($ordered->last()->line1)->toBe('Zebra Lane');
    });
});
