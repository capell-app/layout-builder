# Capabilities

Agent Bridge exposes small, auditable capabilities to the Laravel Boost server and the authenticated Capell Site Agent Bridge server. Capabilities must be registered through `CapellAgentBridgeCapabilityRegistry`; do not expose arbitrary services or controllers as tools.

## Register a Capability Provider

Packages can register a provider by tagging it with `CapellAgentBridgeCapabilityProvider::class`.

```php
use Capell\AgentBridge\Contracts\CapellAgentBridgeCapabilityProvider;
use Capell\AgentBridge\Data\CapabilityData;
use Capell\AgentBridge\Enums\CapabilityRiskEnum;
use Capell\AgentBridge\Enums\CapabilityServerEnum;
use Capell\AgentBridge\Support\CapellAgentBridgeCapabilityRegistry;

final class DemoCapabilityProvider implements CapellAgentBridgeCapabilityProvider
{
    public function registerCapabilities(CapellAgentBridgeCapabilityRegistry $registry): void
    {
        $registry->register(new CapabilityData(
            key: 'demo.clear-cache',
            name: 'Clear demo cache',
            description: 'Preview or clear the demo package cache.',
            scope: 'demo.cache.write',
            server: CapabilityServerEnum::Site,
            risk: CapabilityRiskEnum::Medium,
            actionClass: ClearDemoCacheCapabilityAction::class,
            requiredPackage: 'capell-app/demo-kit',
            supportsPreview: true,
            requiresConfirmation: true,
            auditEvent: 'demo.cache.clear',
        ));
    }
}

$this->app->singleton(DemoCapabilityProvider::class);
$this->app->tag(DemoCapabilityProvider::class, CapellAgentBridgeCapabilityProvider::class);
```

Keys and scopes are part of the external contract. Keep them stable once a client has been issued scopes.

## Write a Capability Action

Actions implement both `preview()` and `execute()`. Mutating actions should return enough preview data for a human or calling agent to see what will change before confirming.

```php
use Capell\AgentBridge\Contracts\CapellAgentBridgeCapabilityAction;
use Capell\AgentBridge\Data\CapabilityInvocationData;
use Capell\AgentBridge\Data\CapabilityResultData;

final class ClearDemoCacheCapabilityAction implements CapellAgentBridgeCapabilityAction
{
    public function preview(CapabilityInvocationData $invocation): CapabilityResultData
    {
        return new CapabilityResultData(
            ok: true,
            message: 'Demo cache would be cleared.',
            data: [
                'cache_key' => 'capell.demo-kit',
            ],
        );
    }

    public function execute(CapabilityInvocationData $invocation): CapabilityResultData
    {
        cache()->forget('capell.demo-kit');

        return new CapabilityResultData(
            ok: true,
            message: 'Demo cache cleared.',
        );
    }
}
```

Keep side effects in `execute()`. `preview()` should not write.

## Risk And Confirmation

`CapabilityData::needsConfirmation()` returns true when either `requiresConfirmation` is true or the risk enum requires it. Use lower risk only for read-only actions.

| Field             | Meaning                                                                             |
| ----------------- | ----------------------------------------------------------------------------------- |
| `scope`           | Token scope required to see and invoke the capability.                              |
| `server`          | Server visibility, such as Boost or authenticated Site bridge.                      |
| `risk`            | Risk level used by confirmation rules.                                              |
| `requiredPackage` | Optional package name checked through Capell Core before the capability is visible. |
| `policyAbility`   | Optional authorization ability.                                                     |
| `auditEvent`      | Event name stored by the audit action.                                              |

## Config Keys

| Key                                               | Use                                                                        |
| ------------------------------------------------- | -------------------------------------------------------------------------- |
| `capell-agent-bridge.routes.*`                    | Route registration switches. Set a route to `null` to stop registering it. |
| `capell-agent-bridge.confirmation_ttl_minutes`    | Lifetime of confirmation tokens for mutating capabilities.                 |
| `capell-agent-bridge.public_docs_paths`           | Documentation paths exposed to knowledge tools.                            |
| `capell-agent-bridge.enable_user_resource_bridge` | Adds token and bridge controls to the user admin resource.                 |
| `capell-agent-bridge.home`                        | Home route content.                                                        |
| `capell-agent-bridge.knowledge`                   | Knowledge server settings.                                                 |

Token, confirmation, and audit table names are migration concerns. Keep them out of public setup docs unless a host app overrides storage.

## Verification

```bash
vendor/bin/pest packages/agent-bridge/tests --configuration=phpunit.xml
```
