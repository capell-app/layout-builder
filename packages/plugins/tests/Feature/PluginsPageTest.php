<?php

declare(strict_types=1);

use Capell\Plugins\Filament\Pages\Plugins\Tables\PluginsTable;
use Capell\Plugins\Filament\Pages\PluginsPage;
use Capell\Plugins\Filament\Resources\MarketplacePluginResource;
use Capell\Plugins\Models\MarketplacePlugin;

test('plugins page class is defined', function (): void {
    expect(class_exists(PluginsPage::class))->toBeTrue();
});

test('plugins page has correct slug', function (): void {
    expect(PluginsPage::getSlug())->toBe('marketplace-plugins');
});

test('plugins page has correct navigation icon', function (): void {
    expect(PluginsPage::$navigationIcon)->not->toBeNull();
});

test('plugins page has correct tabs', function (): void {
    $tabs = (new PluginsPage)->getTabs();
    expect($tabs)->toHaveKeys(['browse', 'installed', 'updates']);
});

test('plugins page has browse, installed, and updates tabs', function (): void {
    $page = new PluginsPage;
    expect($page->isBrowseTab())->toBeFalse();
    expect($page->isInstalledTab())->toBeFalse();
    expect($page->isUpdatesTab())->toBeFalse();
});

test('plugins table class is defined', function (): void {
    expect(class_exists(PluginsTable::class))->toBeTrue();
});

test('marketplace plugin resource is defined', function (): void {
    expect(class_exists(MarketplacePluginResource::class))->toBeTrue();
});

test('marketplace plugin resource has correct model', function (): void {
    expect(MarketplacePluginResource::$model)->toBe(MarketplacePlugin::class);
});
