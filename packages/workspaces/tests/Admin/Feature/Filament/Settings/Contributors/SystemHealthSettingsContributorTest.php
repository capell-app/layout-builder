<?php

declare(strict_types=1);

use Capell\Admin\Filament\Settings\Schemas\DashboardSettingsSchema;
use Capell\Workspaces\Filament\Settings\Contributors\SystemHealthSettingsContributor;

it('declares all system health settings keys', function (): void {
    $entries = (new SystemHealthSettingsContributor)->settingsKeys();
    $keys = collect($entries)->pluck('key')->all();

    expect($keys)->toContain('registry_health', 'migrations_health', 'packages_installed', 'tailwind_build_status', 'workspace_merge_history');
});

it('groups all entries under System health', function (): void {
    $entries = (new SystemHealthSettingsContributor)->settingsKeys();
    foreach ($entries as $entry) {
        expect($entry['group'])->toBe('System health');
    }
});

it('is discovered via the DashboardSettingsContributor tag', function (): void {
    $keys = collect(DashboardSettingsSchema::allContributedKeys())->pluck('key')->all();
    expect($keys)->toContain('registry_health');
});
