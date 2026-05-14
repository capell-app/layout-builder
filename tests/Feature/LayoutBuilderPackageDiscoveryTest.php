<?php

declare(strict_types=1);

use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Illuminate\Support\Facades\Artisan;

it('discovers the layout builder package service provider', function (): void {
    expect(app()->getProvider(LayoutBuilderServiceProvider::class))->not->toBeNull();
});

it('registers the layout builder install command', function (): void {
    expect(array_keys(Artisan::all()))->toContain('capell:layout-builder-install');
});
