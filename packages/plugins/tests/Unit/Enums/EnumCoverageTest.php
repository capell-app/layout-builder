<?php

declare(strict_types=1);

use Capell\Plugins\Enums\Capability;
use Capell\Plugins\Enums\CapabilityWarningLevel;
use Capell\Plugins\Enums\LicenseModel;
use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Enums\PluginKind;

test('plugin kinds cover every supported type', function (): void {
    expect(array_map(static fn (PluginKind $kind): string => $kind->value, PluginKind::cases()))
        ->toEqualCanonicalizing(['theme', 'page_type', 'widget', 'integration', 'content_type', 'full']);
});

test('license models cover free/paid-one-time/paid-subscription', function (): void {
    expect(array_map(static fn (LicenseModel $model): string => $model->value, LicenseModel::cases()))
        ->toEqualCanonicalizing(['free', 'paid_one_time', 'paid_subscription']);
});

test('license statuses are known set', function (): void {
    expect(array_map(static fn (LicenseStatus $status): string => $status->value, LicenseStatus::cases()))
        ->toEqualCanonicalizing(['active', 'trial', 'past_due', 'expired', 'revoked', 'cancelled', 'restricted', 'invalid']);
});

test('capability warning levels are green/yellow/red', function (): void {
    expect(array_map(static fn (CapabilityWarningLevel $level): string => $level->value, CapabilityWarningLevel::cases()))
        ->toEqualCanonicalizing(['green', 'yellow', 'red']);
});

test('capability enum covers all known types', function (): void {
    expect(array_map(static fn (Capability $capability): string => $capability->value, Capability::cases()))
        ->toEqualCanonicalizing([
            'writes_files',
            'db_schema_changes',
            'http_outbound',
            'reads_secrets',
            'admin_pages',
            'frontend_routes',
            'queue_jobs',
            'modifies_core_models',
        ]);
});

test('LicenseModel::isPaid is true for paid plans only', function (): void {
    expect(LicenseModel::Free->isPaid())->toBeFalse();
    expect(LicenseModel::PaidOneTime->isPaid())->toBeTrue();
    expect(LicenseModel::PaidSubscription->isPaid())->toBeTrue();
});

test('LicenseModel::isSubscription is true only for PaidSubscription', function (): void {
    expect(LicenseModel::Free->isSubscription())->toBeFalse();
    expect(LicenseModel::PaidOneTime->isSubscription())->toBeFalse();
    expect(LicenseModel::PaidSubscription->isSubscription())->toBeTrue();
});

test('LicenseStatus::isUsable is true for Active, Trial, PastDue only', function (): void {
    expect(LicenseStatus::Active->isUsable())->toBeTrue();
    expect(LicenseStatus::Trial->isUsable())->toBeTrue();
    expect(LicenseStatus::PastDue->isUsable())->toBeTrue();
    expect(LicenseStatus::Expired->isUsable())->toBeFalse();
    expect(LicenseStatus::Revoked->isUsable())->toBeFalse();
    expect(LicenseStatus::Cancelled->isUsable())->toBeFalse();
    expect(LicenseStatus::Restricted->isUsable())->toBeFalse();
    expect(LicenseStatus::Invalid->isUsable())->toBeFalse();
});
