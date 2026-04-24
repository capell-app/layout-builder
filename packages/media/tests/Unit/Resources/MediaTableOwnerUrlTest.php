<?php

declare(strict_types=1);

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Media\Filament\Resources\Media\Tables\MediaTable;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

it('returns null for media without model', function (): void {
    $media = new Media;

    $reflection = new ReflectionClass(MediaTable::class);
    $method = $reflection->getMethod('getOwnerUrl');

    $url = $method->invoke(null, $media);
    expect($url)->toBeNull();
});

it('returns null when model resource is not registered', function (): void {
    // Register Page resource
    CapellAdmin::registerResource('Page', PageResource::class);

    // Create a media model with a non-existent model type
    $media = new Media;
    $media->model_type = 'NonExistentModel';
    $media->model_id = 1;

    $reflection = new ReflectionClass(MediaTable::class);
    $method = $reflection->getMethod('getOwnerUrl');

    // This should NOT throw, but return null gracefully
    $url = $method->invoke(null, $media);
    expect($url)->toBeNull();
});
