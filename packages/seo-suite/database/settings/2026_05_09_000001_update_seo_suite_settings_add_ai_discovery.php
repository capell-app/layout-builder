<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = [
            'seo_suite.ai_discovery_audit_enabled' => true,
            'seo_suite.ai_discovery_default_enabled' => true,
            'seo_suite.ai_discovery_crawler_policy' => 'search_visible_training_restricted',
        ];

        foreach ($defaults as $key => $value) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $value);
            }
        }
    }
};
