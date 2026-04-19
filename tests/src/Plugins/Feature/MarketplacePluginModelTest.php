<?php

declare(strict_types=1);

use Capell\Plugins\Enums\LicenseModel;
use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Enums\PluginKind;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Models\PluginAuditLogEntry;
use Illuminate\Support\Facades\DB;

test('plugin row casts enums and JSON arrays correctly', function (): void {
    $plugin = MarketplacePlugin::query()->create([
        'slug' => 'mosaic',
        'composer_name' => 'capell-app/mosaic',
        'name' => 'Mosaic',
        'vendor' => 'capell',
        'description' => 'Visual layout builder',
        'kind' => PluginKind::Full,
        'license_model' => LicenseModel::Free,
        'categories' => ['layout', 'content'],
        'capabilities' => ['admin_pages', 'db_schema_changes'],
    ]);

    $fresh = $plugin->fresh();
    expect($fresh->kind)->toBe(PluginKind::Full);
    expect($fresh->license_model)->toBe(LicenseModel::Free);
    expect($fresh->categories->toArray())->toBe(['layout', 'content']);
    expect($fresh->capabilities->toArray())->toBe(['admin_pages', 'db_schema_changes']);
});

test('license row encrypts the key and decrypts on read', function (): void {
    $plugin = MarketplacePlugin::query()->create([
        'slug' => 'assistant',
        'composer_name' => 'capell-app/assistant',
        'name' => 'Assistant',
        'vendor' => 'capell',
        'description' => 'AI helper',
        'kind' => PluginKind::Integration,
        'license_model' => LicenseModel::PaidSubscription,
    ]);

    $license = $plugin->licenses()->create([
        'encrypted_license_key' => 'secret-key-value',
        'status' => LicenseStatus::Active,
    ]);

    $raw = DB::table('marketplace_plugin_licenses')->where('id', $license->id)->value('encrypted_license_key');
    expect($raw)->not->toBe('secret-key-value');
    expect($license->fresh()->encrypted_license_key)->toBe('secret-key-value');
});

test('audit log entry persists JSON data and has no updated_at', function (): void {
    $plugin = MarketplacePlugin::query()->create([
        'slug' => 'blog',
        'composer_name' => 'capell-app/blog',
        'name' => 'Blog',
        'vendor' => 'capell',
        'description' => 'Article page type and tags',
        'kind' => PluginKind::PageType,
        'license_model' => LicenseModel::Free,
    ]);

    $entry = PluginAuditLogEntry::query()->create([
        'marketplace_plugin_id' => $plugin->id,
        'action' => 'installed',
        'data' => ['version' => '1.0.0'],
    ]);

    expect($entry->fresh()->data->toArray())->toBe(['version' => '1.0.0']);
    expect($entry->updated_at ?? null)->toBeNull();
});

test('activeLicense returns usable licenses only', function (): void {
    $plugin = MarketplacePlugin::query()->create([
        'slug' => 'address',
        'composer_name' => 'capell-app/address',
        'name' => 'Address',
        'vendor' => 'capell',
        'description' => 'Country / address models',
        'kind' => PluginKind::Integration,
        'license_model' => LicenseModel::Free,
    ]);

    $plugin->licenses()->create([
        'encrypted_license_key' => 'expired-key',
        'status' => LicenseStatus::Expired,
    ]);

    $active = $plugin->licenses()->create([
        'encrypted_license_key' => 'active-key',
        'status' => LicenseStatus::Active,
    ]);

    expect($plugin->activeLicense()?->id)->toBe($active->id);
});
