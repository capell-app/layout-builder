<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Admin\Filament\Support\HelperText;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class AgentBridgeSettingsSchema implements HasSchema
{
    public static function make(Schema $schema): array
    {
        return [
            HelperText::apply(
                Toggle::make('enable_user_resource_bridge')
                    ->label(__('capell-agent-bridge::admin.enable_user_resource_bridge')),
                'capell-agent-bridge::admin.enable_user_resource_bridge_helper',
            ),
        ];
    }
}
