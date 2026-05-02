<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = [
            'site_search.enabled' => true,
            'site_search.show_header_search' => true,
            'site_search.results_per_page' => 10,
            'site_search.driver' => 'database',
            'site_search.record_search_logs' => true,
            'site_search.log_retention_days' => 180,
            'site_search.hash_visitor_data' => true,
            'site_search.minimum_query_length' => 2,
        ];

        foreach ($defaults as $key => $value) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $value);
            }
        }
    }
};
