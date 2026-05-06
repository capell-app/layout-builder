<?php

declare(strict_types=1);

use Capell\Admin\Filament\Settings\Schemas\DashboardSettingsSchema;
use Capell\PublishingStudio\Filament\Settings\Contributors\DefaultDashboardSettingsContributor;

it('declares one settings entry per default-dashboard widget', function (): void {
    $entries = (new DefaultDashboardSettingsContributor)->settingsKeys();
    $keys = collect($entries)->pluck('key')->all();

    expect($keys)->toContain(
        'setup_health',
        'my_work_queue',
        'recently_published',
        'content_health',
        'site_traffic',
        'top_pages',
        'cache_health',
        'workspace_activity',
    )->not->toContain('login_audits');
});

it('groups entries as Setup / Editor / Admin', function (): void {
    $entries = (new DefaultDashboardSettingsContributor)->settingsKeys();
    $byKey = collect($entries)->keyBy('key');

    expect($byKey['setup_health']['group'])->toBe('Setup');
    expect($byKey['my_work_queue']['group'])->toBe('Editor');
    expect($byKey['site_traffic']['group'])->toBe('Admin');
});

it('is discovered via the DashboardSettingsContributor tag', function (): void {
    $keys = collect(DashboardSettingsSchema::allContributedKeys())->pluck('key')->all();
    expect($keys)->toContain('setup_health', 'site_traffic');
});
