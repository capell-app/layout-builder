# Capell Plugins

A unified marketplace and plugin management system for Capell CMS. Discover, install, and manage first-party and third-party plugins directly from your admin panel.

## Features

- **Plugin Marketplace** — Browse and install plugins from the Capell marketplace
- **First-party Plugins** — Pre-configured support for Mosaic, Blog, Assistant, and Address packages
- **License Management** — Track active, trial, and expired licenses with per-seat granularity
- **Audit Logging** — Monitor all plugin installations, updates, and activations
- **Anystack Integration** — Seamless plugin discovery and installation via Anystack API

## Installation

### 1. Install the Package

```bash
composer require capell-app/capell-plugins
```

### 2. Configure Environment

Create or update your `.env` file:

```env
CAPELL_PLUGINS_ENABLED=true
CAPELL_PLUGINS_ANYSTACK_API_KEY=your-anystack-api-key-here
CAPELL_PLUGINS_ANYSTACK_SECRET=your-anystack-secret-here
```

Get your Anystack API credentials from [anystack.com](https://anystack.com) (Capell partner).

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Seed First-party Plugins (Optional)

Populate the marketplace with first-party Capell plugins (Mosaic, Blog, Assistant, Address):

```bash
php artisan db:seed --class="Capell\Plugins\Database\Seeders\FirstPartyPluginsSeeder"
```

## Configuration

Edit `config/capell-plugins.php` to customize behavior:

```php
return [
    'enabled' => env('CAPELL_PLUGINS_ENABLED', false),
    'anystack' => [
        'api_key' => env('CAPELL_PLUGINS_ANYSTACK_API_KEY'),
        'secret' => env('CAPELL_PLUGINS_ANYSTACK_SECRET'),
        'endpoint' => env('CAPELL_PLUGINS_ANYSTACK_ENDPOINT', 'https://api.anystack.com'),
    ],
    'default_page_size' => 12,
    'cache_ttl' => 3600,
];
```

## Usage

Once configured, access the Plugin Marketplace from the Capell admin panel:

1. Navigate to **Settings → Plugins** (or **Plugins** in the main navigation)
2. Browse available plugins in the **Marketplace** tab
3. Install plugins with one click — the system handles dependencies and database migrations
4. Manage installed plugins and licenses in the **Installed** tab

## First-party Plugins

The `FirstPartyPluginsSeeder` automatically registers these plugins:

| Plugin | Description | Capabilities |
|--------|-------------|--------------|
| **Mosaic** | Visual layout builder, widgets, and reusable content items | Admin pages, schema changes, frontend routes |
| **Blog** | Article page type, tags, archives, and listing pages | Admin pages, schema changes, frontend routes |
| **Assistant** | OpenAI-powered content drafting (requires API key) | Admin pages, queue jobs, external API calls |
| **Address** | Country and address models for site settings | Admin pages, schema changes |

## License Handling

Plugins may use different license models:

- **Free** — Always available, no license required
- **Paid (One-time)** — One-time purchase license
- **Paid (Subscription)** — Recurring subscription license

Active licenses are tracked in the `marketplace_plugin_licenses` table with status (`active`, `trial`, `past_due`, `expired`).

## Disabling the Legacy PluginsPage

If capell-plugins is enabled, the legacy PluginsPage (in Capell Admin) is automatically hidden. To explicitly use the new system, set `CAPELL_PLUGINS_ENABLED=true` in your environment.

## Troubleshooting

**Plugins not appearing in marketplace:**
- Verify `CAPELL_PLUGINS_ENABLED=true` is set in `.env`
- Check Anystack API credentials are valid
- Run `php artisan cache:clear` to refresh cached plugin listings

**License validation fails:**
- Ensure `CAPELL_PLUGINS_ANYSTACK_SECRET` is correctly configured
- Verify the license file is stored in `storage/capell-plugins/licenses/`
- Check audit logs for validation errors

**Migrations not running:**
- Run `php artisan migrate` explicitly
- Verify migrations are in `packages/plugins/database/migrations/`

## Architecture

For detailed API reference and database schema, see:
- [API Reference](./docs/API.md)
- [Database Schema](./docs/Database.md)

## Support

- GitHub Issues: [capell-app/capell-plugins](https://github.com/capell-app/capell-plugins/issues)
- Documentation: [docs.capell.app](https://docs.capell.app)
