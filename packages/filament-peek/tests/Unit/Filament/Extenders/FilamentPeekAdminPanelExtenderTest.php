<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\FilamentPeek\Filament\Extenders\FilamentPeekAdminPanelExtender;
use Filament\Panel;
use Pboivin\FilamentPeek\FilamentPeekPlugin;

it('implements the admin panel extender contract', function (): void {
    expect(FilamentPeekAdminPanelExtender::class)
        ->toImplement(AdminPanelExtender::class);
});

it('is tagged as an admin panel extender', function (): void {
    $extenders = collect(app()->tagged(AdminPanelExtender::TAG))
        ->map(fn (object $extender): string => $extender::class)
        ->all();

    expect($extenders)->toContain(FilamentPeekAdminPanelExtender::class);
});

it('registers the filament peek plugin once', function (): void {
    $panel = Panel::make();
    $extender = new FilamentPeekAdminPanelExtender;

    $extender->extend($panel);
    $extender->extend($panel);

    expect($panel->hasPlugin(FilamentPeekPlugin::ID))->toBeTrue()
        ->and($panel->getPlugins())->toHaveCount(1);
});
