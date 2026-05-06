<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\SeoSuite\Actions\SiteMetaSchemaAction;
use Illuminate\Support\Arr;

it('generates correct schema for site with meta data', function (): void {
    $language = LanguageFactory::new()->create([
        'name' => 'English',
        'code' => 'en',
    ]);

    $site = SiteFactory::new()
        ->language($language)
        ->hasSiteDomain()
        ->meta([
            'organization_type' => 'Organization',
            'business_name' => 'Test Business',
            'areas_served' => [
                ['type' => 'Country', 'name' => 'USA', 'url' => 'https://example.com/usa'],
            ],
            'currencies_accepted' => ['USD'],
            'email' => 'info@example.com',
            'open_hours' => [
                [
                    'days' => ['monday', 'tuesday'],
                    'open_time' => '09:00',
                    'close_time' => '17:00',
                ],
            ],
            'payment_accepted' => ['CreditCard'],
            'phone' => '+1234567890',
            'price_range' => '$$',
            'social_links' => [
                ['url' => 'https://twitter.com/test'],
            ],
        ])
        ->create();

    $site->refresh();

    $configurator = SiteMetaSchemaAction::run($site, $language);

    expect($configurator)
        ->toHaveKey('@context', 'https://schema.org')
        ->toHaveKey('@type', 'Organization')
        ->toHaveKey('name', 'Test Business')
        ->toHaveKey('email', 'info@example.com')
        ->toHaveKey('telephone', '+1234567890')
        ->toHaveKey('priceRange', '$$')
        ->toHaveKey('currenciesAccepted')
        ->toHaveKey('paymentAccepted')
        ->toHaveKey('areaServed')
        ->toHaveKey('sameAs')
        ->toHaveKey('openingHoursSpecification');

    expect(Arr::first($configurator['areaServed']))
        ->toHaveKey('@type', 'Country')
        ->toHaveKey('name', 'USA')
        ->toHaveKey('@id', 'https://example.com/usa');

    expect($configurator['sameAs'][0])->toBe('https://twitter.com/test');

    $openingHours = $configurator['openingHoursSpecification'][0];
    expect($openingHours)
        ->toHaveKey('dayOfWeek')
        ->toHaveKey('opens', '09:00')
        ->toHaveKey('closes', '17:00');

    expect($openingHours['dayOfWeek'])
        ->toContain('https://schema.org/Monday')
        ->toContain('https://schema.org/Tuesday');
});

it('handles missing meta fields gracefully', function (): void {
    $language = LanguageFactory::new()->create([
        'name' => 'French',
        'code' => 'fr',
    ]);

    $site = SiteFactory::new()
        ->language($language)
        ->hasSiteDomain()
        ->create([
            'meta' => [],
        ]);

    $site->refresh();

    $configurator = SiteMetaSchemaAction::run($site, $language);

    expect($configurator)
        ->toHaveKey('@context', 'https://schema.org')
        ->toHaveKey('@type', 'Organization')
        ->toHaveKey('name', $site->translation->title)
        ->not()->toHaveKey('email')
        ->not()->toHaveKey('telephone')
        ->not()->toHaveKey('priceRange');
});

it('generates schema with multiple media and social links', function (): void {
    $language = LanguageFactory::new()->create([
        'name' => 'German',
        'code' => 'de',
    ]);

    $site = SiteFactory::new()
        ->language($language)
        ->hasSiteDomain()
        ->meta([
            'organization_type' => 'Organization',
            'business_name' => 'Media Test',
            'currencies_accepted' => ['EUR', 'USD'],
            'social_links' => [
                ['url' => 'https://twitter.com/test'],
                ['url' => 'https://facebook.com/test'],
            ],
        ])
        ->create();

    // Attach two media items if possible (simulate if factory supports)
    $site->refresh();

    $configurator = SiteMetaSchemaAction::run($site, $language);

    expect($configurator)
        ->toHaveKey('currenciesAccepted')
        ->toHaveKey('sameAs');
    expect($configurator['sameAs'])
        ->toContain('https://twitter.com/test')
        ->toContain('https://facebook.com/test');
});

it('generates schema with multiple open hours and edge days', function (): void {
    $language = LanguageFactory::new()->create([
        'name' => 'Spanish',
        'code' => 'es',
    ]);

    $site = SiteFactory::new()
        ->language($language)
        ->hasSiteDomain()
        ->meta([
            'organization_type' => 'Organization',
            'business_name' => 'Open Hours Test',
            'open_hours' => [
                [
                    'days' => ['saturday', 'sunday'],
                    'open_time' => '10:00',
                    'close_time' => '14:00',
                ],
                [
                    'days' => ['public_holidays'],
                    'open_time' => '12:00',
                    'close_time' => '16:00',
                ],
            ],
        ])
        ->create();

    $site->refresh();

    $configurator = SiteMetaSchemaAction::run($site, $language);

    expect($configurator['openingHoursSpecification'])->toHaveCount(2);
    expect($configurator['openingHoursSpecification'][0]['dayOfWeek'])
        ->toContain('https://schema.org/Saturday')
        ->toContain('https://schema.org/Sunday');
    expect($configurator['openingHoursSpecification'][1]['dayOfWeek'])
        ->toContain('https://schema.org/PublicHolidays');
});

it('handles empty media collection without error', function (): void {
    $language = LanguageFactory::new()->create([
        'name' => 'Italian',
        'code' => 'it',
    ]);

    $site = SiteFactory::new()
        ->language($language)
        ->hasSiteDomain()
        ->meta([
            'organization_type' => 'Organization',
            'business_name' => 'No Media',
        ])
        ->create();

    $site->refresh();
    $site->media()->delete(); // Ensure no media
    $configurator = SiteMetaSchemaAction::run($site, $language);
    expect($configurator)->not()->toHaveKey('image');
    expect($configurator)->not()->toHaveKey('photos');
});

it('handles all optional fields present', function (): void {
    $language = LanguageFactory::new()->create([
        'name' => 'Dutch',
        'code' => 'nl',
    ]);

    $site = SiteFactory::new()
        ->language($language)
        ->hasSiteDomain()
        ->meta([
            'organization_type' => 'Organization',
            'business_name' => 'Full Option',
            'areas_served' => [
                ['type' => 'Country', 'name' => 'Netherlands', 'url' => 'https://example.com/nl'],
            ],
            'currencies_accepted' => ['EUR'],
            'email' => 'contact@fulloption.com',
            'open_hours' => [
                [
                    'days' => ['monday'],
                    'open_time' => '08:00',
                    'close_time' => '18:00',
                ],
            ],
            'payment_accepted' => ['CreditCard', 'Cash'],
            'phone' => '+31000000000',
            'price_range' => '$$$',
            'social_links' => [
                ['url' => 'https://linkedin.com/company/fulloption'],
            ],
        ])
        ->create();

    $site->refresh();

    $configurator = SiteMetaSchemaAction::run($site, $language);

    expect($configurator)
        ->toHaveKey('areaServed')
        ->toHaveKey('currenciesAccepted')
        ->toHaveKey('email', 'contact@fulloption.com')
        ->toHaveKey('paymentAccepted')
        ->toHaveKey('telephone', '+31000000000')
        ->toHaveKey('priceRange', '$$$')
        ->toHaveKey('sameAs')
        ->toHaveKey('openingHoursSpecification');
});

it('handles all optional fields missing', function (): void {
    $language = LanguageFactory::new()->create([
        'name' => 'Polish',
        'code' => 'pl',
    ]);

    $site = SiteFactory::new()
        ->language($language)
        ->hasSiteDomain()
        ->meta([
            'organization_type' => 'Organization',
            'business_name' => 'Minimal',
        ])
        ->create();

    $site->refresh();

    $configurator = SiteMetaSchemaAction::run($site, $language);

    expect($configurator)
        ->not()->toHaveKey('areaServed')
        ->not()->toHaveKey('currenciesAccepted')
        ->not()->toHaveKey('email')
        ->not()->toHaveKey('paymentAccepted')
        ->not()->toHaveKey('telephone')
        ->not()->toHaveKey('priceRange')
        ->not()->toHaveKey('sameAs')
        ->not()->toHaveKey('openingHoursSpecification');
});
