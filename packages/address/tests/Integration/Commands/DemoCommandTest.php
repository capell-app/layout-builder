<?php

declare(strict_types=1);

use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;

use function Pest\Laravel\artisan;

describe('capell:address-demo command', function (): void {
    it('creates demo address and links it to the site', function (): void {
        $languageModel = Language::class;
        $language = $languageModel::factory()->english()->create();

        $siteModel = Site::class;
        $site = $siteModel::factory()
            ->language($language)
            ->create([
                'name' => 'Demo Site',
            ]);

        artisan('capell:address-demo', [
            '--sites' => $site->name,
        ])
            ->expectsOutputToContain('Selected site: ' . $site->name)
            ->expectsOutputToContain('Demo address content has been successfully created for site: ' . $site->name)
            ->assertExitCode(0);

        $country = Country::query()->firstWhere('iso2', 'US');

        $address = Address::findAddress(
            line1: '123 Main St',
            postalCode: '12345',
            countryId: $country->id,
        );

        expect($site->refresh())
            ->meta->address_id->toBe($address->id);
    });
});
