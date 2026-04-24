<?php

declare(strict_types=1);

use Capell\Address\Models\Address;
use Capell\Address\Support\Address\AddressUrl;

it('returns a Google Maps URL for a model address', function (): void {
    $address = new Address([
        'line1' => '123 Main St',
        'meta' => [
            'latitude' => '40.7128',
            'longitude' => '-74.0060',
        ],
    ]);
    $url = AddressUrl::url($address);
    expect($url)->toBe('https://www.google.com/maps/search/?api=1&query=123+Main+St@40.7128,-74.0060');
});
