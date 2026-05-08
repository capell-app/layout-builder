<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = [
            'seo_suite.seo_audit_enabled' => true,
            'seo_suite.seo_check_meta_description' => true,
            'seo_suite.seo_check_meta_title' => true,
            'seo_suite.seo_check_duplicate_title' => true,
        ];

        foreach ($defaults as $key => $value) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $value);
            }
        }
    }
};
