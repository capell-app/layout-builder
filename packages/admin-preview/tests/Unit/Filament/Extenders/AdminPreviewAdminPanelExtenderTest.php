<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\AdminPreview\Filament\Extenders\AdminPreviewAdminPanelExtender;
use Filament\Panel;
use Pboivin\AdminPreview\AdminPreviewPlugin;

it('implements the admin panel extender contract', function (): void {
    expect(AdminPreviewAdminPanelExtender::class)
        ->toImplement(AdminPanelExtender::class);
});

it('is tagged as an admin panel extender', function (): void {
    $extenders = collect(app()->tagged(AdminPanelExtender::TAG))
        ->map(fn (object $extender): string => $extender::class)
        ->all();

    expect($extenders)->toContain(AdminPreviewAdminPanelExtender::class);
});

it('registers the filament peek plugin once', function (): void {
    $panel = Panel::make();
    $extender = new AdminPreviewAdminPanelExtender;

    $extender->extend($panel);
    $extender->extend($panel);

    expect($panel->hasPlugin(AdminPreviewPlugin::ID))->toBeTrue()
        ->and($panel->getPlugins())->toHaveCount(1);
});
