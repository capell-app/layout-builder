<?php

declare(strict_types=1);

use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Tests\TestCase;

uses(TestCase::class);

it('passes doctor checks for the default test installation', function (): void {
    $this->artisan('capell:access-gate-doctor')
        ->assertSuccessful();
});

it('fails doctor checks when page cache can run before the access gate', function (): void {
    app('router')->pushMiddlewareToGroup('web', 'page-cache');

    $this->artisan('capell:access-gate-doctor')
        ->assertFailed();
});

it('sets up the configured default access area', function (): void {
    config()->set('access-gate.install.default_area.key', 'capell-preview');
    config()->set('access-gate.install.default_area.name', 'Capell Preview');

    $this->artisan('capell:access-gate-setup')
        ->assertSuccessful();

    expect(Area::query()->where('key', 'capell-preview')->where('name', 'Capell Preview')->exists())->toBeTrue();
});
