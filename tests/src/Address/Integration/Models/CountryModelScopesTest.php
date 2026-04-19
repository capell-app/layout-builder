<?php

declare(strict_types=1);

use Capell\Address\Models\Country;

describe('Country model scopes', function (): void {
    beforeEach(function (): void {
        Country::query()->delete();
    });

    it('can query default countries', function (): void {
        Country::factory()->create(['default' => true]);
        Country::factory()->create(['default' => false]);
        Country::factory()->create(['default' => true]);

        $defaults = Country::default()->get();

        expect($defaults)->toHaveCount(2);
        expect($defaults->every(fn (Country $c) => $c->default))->toBeTrue();
    });

    it('can query non-default countries', function (): void {
        Country::factory()->create(['default' => true]);
        Country::factory()->create(['default' => false]);
        Country::factory()->create(['default' => false]);

        $nonDefaults = Country::nonDefault()->get();

        expect($nonDefaults)->toHaveCount(2);
        expect($nonDefaults->every(fn (Country $c) => ! $c->default))->toBeTrue();
    });

    it('can query enabled countries', function (): void {
        Country::factory()->create(['status' => true]);
        Country::factory()->create(['status' => false]);
        Country::factory()->create(['status' => true]);

        $enabled = Country::enabled()->get();

        expect($enabled)->toHaveCount(2);
        expect($enabled->every(fn (Country $c) => $c->status))->toBeTrue();
    });

    it('can query disabled countries', function (): void {
        Country::factory()->create(['status' => true]);
        Country::factory()->create(['status' => false]);
        Country::factory()->create(['status' => false]);

        $disabled = Country::disabled()->get();

        expect($disabled)->toHaveCount(2);
        expect($disabled->every(fn (Country $c) => ! $c->status))->toBeTrue();
    });

    it('can query countries by status', function (): void {
        Country::factory()->create(['status' => true]);
        Country::factory()->create(['status' => false]);
        Country::factory()->create(['status' => true]);

        $statusTrue = Country::status(true)->get();

        expect($statusTrue)->toHaveCount(2);
    });

    it('returns countries ordered by name', function (): void {
        Country::factory()->create(['name' => 'Zebra Land']);
        Country::factory()->create(['name' => 'Apple Country']);
        Country::factory()->create(['name' => 'Banana Nation']);

        $ordered = Country::ordered()->get();

        expect($ordered->pluck('name')->toArray())->toBe([
            'Apple Country',
            'Banana Nation',
            'Zebra Land',
        ]);
    });
});
