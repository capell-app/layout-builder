<?php

declare(strict_types=1);

use Capell\Address\Models\Country;

describe('CountryObserver', function (): void {
    it('sets first country as default on creation', function (): void {
        Country::query()->delete();

        $country = Country::factory()->create(['default' => false]);

        expect($country->default)->toBeTrue();
    });

    it('does not override explicit default value if country exists', function (): void {
        Country::query()->delete();
        Country::factory()->create(['default' => true]);

        $country = Country::factory()->create(['default' => false]);

        expect($country->default)->toBeFalse();
    });

    it('respects explicit true default for subsequent countries', function (): void {
        Country::query()->delete();
        Country::factory()->create(['default' => true]);

        $country = Country::factory()->create(['default' => true]);

        expect($country->default)->toBeTrue();
    });
});
