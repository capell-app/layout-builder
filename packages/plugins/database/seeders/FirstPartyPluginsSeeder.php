<?php

declare(strict_types=1);

namespace Capell\Plugins\Database\Seeders;

use Capell\Plugins\Enums\LicenseModel;
use Capell\Plugins\Enums\PluginKind;
use Capell\Plugins\Models\MarketplacePlugin;
use Illuminate\Database\Seeder;

class FirstPartyPluginsSeeder extends Seeder
{
    public function run(): void
    {
        MarketplacePlugin::updateOrCreate(['slug' => 'mosaic'], [
            'composer_name' => 'capell-app/capell-mosaic',
            'title' => 'Mosaic',
            'vendor' => 'capell',
            'description' => 'Visual layout builder, widgets, and reusable content items.',
            'kind' => PluginKind::Full,
            'license_model' => LicenseModel::Free,
            'categories' => ['layout', 'content'],
            'capabilities' => ['admin_pages', 'db_schema_changes', 'frontend_routes'],
            'is_visible' => true,
            'sort_order' => 10,
        ]);

        MarketplacePlugin::updateOrCreate(['slug' => 'blog'], [
            'composer_name' => 'capell-app/capell-blog',
            'title' => 'Blog',
            'vendor' => 'capell',
            'description' => 'Article page type, tags, archives, and Livewire listing pages.',
            'kind' => PluginKind::Full,
            'license_model' => LicenseModel::Free,
            'categories' => ['content', 'page-types'],
            'capabilities' => ['admin_pages', 'db_schema_changes', 'frontend_routes'],
            'is_visible' => true,
            'sort_order' => 20,
        ]);

        MarketplacePlugin::updateOrCreate(['slug' => 'assistant'], [
            'composer_name' => 'capell-app/capell-assistant',
            'title' => 'Assistant',
            'vendor' => 'capell',
            'description' => 'OpenAI-powered title, meta, and content drafting with audit logging.',
            'kind' => PluginKind::Full,
            'license_model' => LicenseModel::Free,
            'categories' => ['ai', 'content-tools'],
            'capabilities' => ['admin_pages', 'queue_jobs', 'external_api_calls'],
            'is_visible' => true,
            'sort_order' => 30,
        ]);

        MarketplacePlugin::updateOrCreate(['slug' => 'address'], [
            'composer_name' => 'capell-app/capell-address',
            'title' => 'Address',
            'vendor' => 'capell',
            'description' => 'Country and address models for site configuration.',
            'kind' => PluginKind::Full,
            'license_model' => LicenseModel::Free,
            'categories' => ['site-settings'],
            'capabilities' => ['admin_pages', 'db_schema_changes'],
            'is_visible' => true,
            'sort_order' => 40,
        ]);
    }
}
