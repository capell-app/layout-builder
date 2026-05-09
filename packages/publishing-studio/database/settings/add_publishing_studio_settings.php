<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('publishing_studio.enable_user_resource_bridge')) {
            $this->migrator->add('publishing_studio.enable_user_resource_bridge', true);
        }
    }
};
