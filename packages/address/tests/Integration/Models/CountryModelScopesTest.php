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

        $defaults = Country::query()->default()->get();

        expect($defaults)->toHaveCount(2);
        expect($defaults->every(fn (Country $c) => $c->default))->toBeTrue();
    });

    it('can query non-default countries', function (): void {
        Country::factory()->create(['default' => true]);
        Country::factory()->create(['default' => false]);
        Country::factory()->create(['default' => false]);

        $nonDefaults = Country::query()->nonDefault()->get();

        expect($nonDefaults)->toHaveCount(2);
        expect($nonDefaults->every(fn (Country $c): bool => ! $c->default))->toBeTrue();
    });

    it('can query enabled countries', function (): void {
        Country::factory()->create(['status' => true]);
        Country::factory()->create(['status' => false]);
        Country::factory()->create(['status' => true]);

        $enabled = Country::query()->enabled()->get();

        expect($enabled)->toHaveCount(2);
        expect($enabled->every(fn (Country $c) => $c->status))->toBeTrue();
    });

    it('can query disabled countries', function (): void {
        Country::factory()->create(['status' => true]);
        Country::factory()->create(['status' => false]);
        Country::factory()->create(['status' => false]);

        $disabled = Country::query()->disabled()->get();

        expect($disabled)->toHaveCount(2);
        expect($disabled->every(fn (Country $c): bool => ! $c->status))->toBeTrue();
    });

    it('can query countries by status', function (): void {
        Country::factory()->create(['status' => true]);
        Country::factory()->create(['status' => false]);
        Country::factory()->create(['status' => true]);

        $statusTrue = Country::query()->status(true)->get();

        expect($statusTrue)->toHaveCount(2);
    });

    it('returns countries ordered by name', function (): void {
        Country::factory()->create(['name' => 'Zebra Land']);
        Country::factory()->create(['name' => 'Apple Country']);
        Country::factory()->create(['name' => 'Banana Nation']);

        $ordered = Country::query()->ordered()->get();

        expect($ordered->pluck('name')->toArray())->toBe([
            'Apple Country',
            'Banana Nation',
            'Zebra Land',
        ]);
    });
});
