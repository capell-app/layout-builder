<?php

declare(strict_types=1);

use Capell\Admin\Enums\SetupHealthEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Models\Type;
use Capell\Diagnostics\Actions\Dashboard\BuildSetupHealthAction;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Spatie\Permission\Models\Role;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('dashboard-dashboard_reports red when no sites exist', function (): void {
    $result = BuildSetupHealthAction::run();
    $siteCheck = $result->checks->toCollection()->firstWhere('id', 'site');

    expect($siteCheck->status)->toBe(SetupHealthEnum::Red);
    expect($result->allGreen)->toBeFalse();
});

it('dashboard-dashboard_reports green for sites once one exists', function (): void {
    Site::factory()->create();

    $result = BuildSetupHealthAction::run();
    $siteCheck = $result->checks->toCollection()->firstWhere('id', 'site');

    expect($siteCheck->status)->toBe(SetupHealthEnum::Green);
});

it('dashboard-dashboard_reports allGreen only when every check is green', function (): void {
    Role::findOrCreate('super_admin');

    Site::factory()->create();
    Language::factory()->create();
    Type::factory()->create();
    Theme::factory()->create();

    $adminUser = $this->createUser();
    $adminUser->assignRole('super_admin');

    $result = BuildSetupHealthAction::run();
    expect($result->allGreen)->toBeTrue();
});

it('emits fixLabel on failing checks', function (): void {
    $result = BuildSetupHealthAction::run();
    $siteCheck = $result->checks->toCollection()->firstWhere('id', 'site');

    expect($siteCheck->fixLabel)->toBe('Create site');
});
