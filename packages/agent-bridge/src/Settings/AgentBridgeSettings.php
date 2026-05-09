<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Settings;

use Capell\AgentBridge\Filament\Settings\AgentBridgeSettingsSchema;
use Capell\Core\Contracts\SettingsContract;
use Spatie\LaravelSettings\Settings;

final class AgentBridgeSettings extends Settings implements SettingsContract
{
    public bool $enable_user_resource_bridge = true;

    public static function group(): string
    {
        return 'agent_bridge';
    }

    public static function schema(): string
    {
        return AgentBridgeSettingsSchema::class;
    }
}
