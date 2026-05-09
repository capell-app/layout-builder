<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('agent_bridge.enable_user_resource_bridge')) {
            $this->migrator->add('agent_bridge.enable_user_resource_bridge', true);
        }
    }
};
