<?php

declare(strict_types=1);

use Capell\Address\Models\Country;
use Capell\Core\Models\Language;

describe('Country model', function (): void {
    it('can create a country with attributes', function (): void {
        $country = Country::factory()->create([
            'name' => 'Testland',
            'iso2' => 'TL',
            'iso3' => 'TST',
        ]);
        expect($country->name)->toBe('Testland');
        expect($country->iso2)->toBe('TL');
        expect($country->iso3)->toBe('TST');
    });

    it('can relate to a language', function (): void {
        $language = Language::factory()->create(['name' => 'English']);
        $country = Country::factory()->create(['language_id' => $language->id]);
        expect($country->language)->not()->toBeNull();
        expect($country->language->name)->toBe('English');
    });

    it('can relate to multiple languages via meta', function (): void {
        $language1 = Language::factory()->create(['name' => 'English']);
        $language2 = Language::factory()->create(['name' => 'French']);
        $country = Country::factory()->create([
            'meta' => [
                'languages' => [$language1->id, $language2->id],
            ],
        ]);
        $languages = $country->languages;
        expect($languages)->toHaveCount(2);
        expect($languages->pluck('name'))->toContain('English');
        expect($languages->pluck('name'))->toContain('French');
    });
});
