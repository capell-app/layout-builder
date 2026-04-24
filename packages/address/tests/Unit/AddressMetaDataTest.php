<?php

declare(strict_types=1);

use Capell\Address\Data\AddressMetaData;

describe('AddressMetaData', function (): void {
    it('can create with latitude and longitude', function (): void {
        $data = new AddressMetaData(
            latitude: '40.7128',
            longitude: '-74.0060',
        );

        expect($data->latitude)->toBe('40.7128');
        expect($data->longitude)->toBe('-74.0060');
    });

    it('can create with null values', function (): void {
        $data = new AddressMetaData(
            latitude: null,
            longitude: null,
        );

        expect($data->latitude)->toBeNull();
        expect($data->longitude)->toBeNull();
    });

    it('can create with mixed null and non-null values', function (): void {
        $data = new AddressMetaData(
            latitude: '51.5074',
            longitude: null,
        );

        expect($data->latitude)->toBe('51.5074');
        expect($data->longitude)->toBeNull();
    });
});
