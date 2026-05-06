# Laravel Boost Integration

Capell Agent Bridge has two Agent Bridge surfaces:

- Laravel Boost local Agent Bridge: `php artisan boost:agent-bridge`
- Capell Site Agent Bridge: the authenticated `agent-bridge/capell` route registered by `capell-app/agent-bridge`

Boost is for local development assistance. It can discover Capell package guidance from installed Composer packages and can list or preview registered Capell Agent Bridge capabilities.

The authenticated Capell Site Agent Bridge server is for site operations. It uses Capell Agent Bridge tokens, capability scopes, previews, confirmations, and audit records.

## Package Discovery

Laravel Boost discovers third-party package guidance from installed packages:

- `vendor/capell-app/*/resources/boost/guidelines`
- `vendor/capell-app/*/resources/boost/skills`

Capell packages keep these files intentionally small. They tell the agent what the package is and where to read more, usually `README.md`, `docs/`, and `src/`.

## Installing In A Host App

Install Laravel Boost and Capell Agent Bridge in the Laravel app:

```bash
composer require --dev laravel/boost
composer require capell-app/agent-bridge:*
php artisan package:discover
```

When using zsh, quote the wildcard package constraint:

```bash
composer require 'capell-app/agent-bridge:*'
```

Run Boost installation for the agent you use:

```bash
php artisan boost:install
```

This writes the agent Agent Bridge config for `php artisan boost:agent-bridge`. The exact destination depends on the selected agent.

## How Capell Agent Bridge Appears In Boost

`Capell\AgentBridge\Providers\AgentBridgeServiceProvider` checks whether Boost is installed. When `Laravel\Boost\AgentBridge\Boost` exists, the provider appends Capell bridge tools to:

```php
config('boost.agent-bridge.tools.include')
```

The bridge tools are:

- `capell-list-capabilities`
- `capell-preview-capability`

These tools read from `Capell\AgentBridge\Support\CapellAgentBridgeCapabilityRegistry`, so package capabilities registered through `CapellAgentBridgeCapabilityProvider` become visible to Boost without each package adding its own Boost-specific tool.

## Capability Flow

For local development:

1. Agent connects to `php artisan boost:agent-bridge`.
2. Agent calls `capell-list-capabilities`.
3. Agent calls `capell-preview-capability` for a safe preview.
4. Mutating confirmation is handled outside Boost through the authenticated Capell Site Agent Bridge server.

For authenticated site operations:

1. Create a Capell Agent Bridge token with the required scopes.
2. Connect an Agent Bridge client to the configured `agent-bridge/capell` route.
3. Call the site capability preview tool.
4. Review the preview and confirmation token.
5. Confirm through the site Agent Bridge confirmation tool.
6. Review audit entries if needed.

## Verifying In `capell-ruby`

From the host app:

```bash
cd /Users/ben/Sites/capell-ruby
composer require 'capell-app/agent-bridge:*' --with-all-dependencies
php artisan package:discover --ansi
find vendor/capell-app/agent-bridge/resources/boost -maxdepth 4 -type f -print | sort
php artisan tinker --execute='var_export(config("boost.agent-bridge.tools.include"));'
```

Expected:

- `vendor/capell-app/agent-bridge/resources/boost/guidelines/core.blade.php`
- `vendor/capell-app/agent-bridge/resources/boost/skills/capell-agent-bridge-development/SKILL.md`
- `Capell\AgentBridge\Tools\Boost\ListBoostCapabilitiesTool`
- `Capell\AgentBridge\Tools\Boost\PreviewBoostCapabilityTool`

## Common Problems

- `capell-app/agent-bridge` is not in the host app `composer.json`, so no Capell Agent Bridge provider or Boost resources are installed.
- `php artisan boost:install` has not been run, so the agent is not configured to start `boost:agent-bridge`.
- Config is cached after changing installed packages. Run `php artisan optimize:clear`.
- A package adds an Agent Bridge capability but does not tag a `CapellAgentBridgeCapabilityProvider`, so the registry never sees it.
- A mutating operation is attempted through Boost instead of the authenticated Capell Site Agent Bridge server.
