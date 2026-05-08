<?php

declare(strict_types=1);

use Capell\LoginAudit\Actions\BuildLoginAuditsQueryAction;
use Capell\LoginAudit\Actions\ResolveLoginAuditIpAddressAction;
use Capell\LoginAudit\Models\LoginAudit;
use Capell\LoginAudit\Settings\LoginAuditSettings;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

function seedLoginAuditQueryAndResolverSetting(string $settingName, mixed $value): void
{
    /** @var SettingsMigrator $settingsMigrator */
    $settingsMigrator = resolve(SettingsMigrator::class);
    $settingKey = 'login_audit.' . $settingName;

    if ($settingsMigrator->exists($settingKey)) {
        $settingsMigrator->update($settingKey, fn (): mixed => $value);
    } else {
        $settingsMigrator->add($settingKey, $value);
    }

    app()->forgetInstance(LoginAuditSettings::class);
}

it('builds login audit queries filtered by time window ordered newest first and limited', function (): void {
    $trackedAt = CarbonImmutable::parse('2026-05-07 10:00:00');
    $this->travelTo($trackedAt);

    $newestAudit = LoginAudit::factory()->create([
        'login_at' => $trackedAt->subMinutes(5),
    ]);

    $middleAudit = LoginAudit::factory()->create([
        'login_at' => $trackedAt->subHours(2),
    ]);

    $thirdNewestAudit = LoginAudit::factory()->create([
        'login_at' => $trackedAt->subHours(5),
    ]);

    $limitedOutAudit = LoginAudit::factory()->create([
        'login_at' => $trackedAt->subHours(5)->subMinute(),
    ]);

    $outsideWindowAudit = LoginAudit::factory()->create([
        'login_at' => $trackedAt->subHours(8),
    ]);

    $records = BuildLoginAuditsQueryAction::run(hours: 6, limit: 3)->get();

    expect($records->modelKeys())->toBe([
        $newestAudit->getKey(),
        $middleAudit->getKey(),
        $thirdNewestAudit->getKey(),
    ])->and($records->modelKeys())->not->toContain($limitedOutAudit->getKey())
        ->and($records->modelKeys())->not->toContain($outsideWindowAudit->getKey());
});

it('uses the direct request ip address when proxy headers are not configured', function (): void {
    seedLoginAuditQueryAndResolverSetting('track_user_ip_addresses', true);

    config()->set('login-audit.behind_cdn', false);

    $request = Request::create(
        uri: '/admin/login-audits',
        method: Symfony\Component\HttpFoundation\Request::METHOD_GET,
        server: [
            'REMOTE_ADDR' => '198.51.100.10',
            'HTTP_CF_CONNECTING_IP' => '203.0.113.10',
        ],
    );

    expect(ResolveLoginAuditIpAddressAction::run($request))->toBe('198.51.100.10');
});

it('uses the configured proxy header when cdn mode is configured', function (): void {
    seedLoginAuditQueryAndResolverSetting('track_user_ip_addresses', true);

    config()->set('login-audit.behind_cdn', [
        'http_header_field' => 'HTTP_CF_CONNECTING_IP',
    ]);

    $request = Request::create(
        uri: '/admin/login-audits',
        method: Symfony\Component\HttpFoundation\Request::METHOD_GET,
        server: [
            'REMOTE_ADDR' => '198.51.100.10',
            'HTTP_CF_CONNECTING_IP' => '203.0.113.10',
        ],
    );

    expect(ResolveLoginAuditIpAddressAction::run($request))->toBe('203.0.113.10');
});

it('returns null ip addresses when tracking is disabled', function (): void {
    seedLoginAuditQueryAndResolverSetting('track_user_ip_addresses', false);

    config()->set('login-audit.behind_cdn', [
        'http_header_field' => 'HTTP_CF_CONNECTING_IP',
    ]);

    $request = Request::create(
        uri: '/admin/login-audits',
        method: Symfony\Component\HttpFoundation\Request::METHOD_GET,
        server: [
            'REMOTE_ADDR' => '198.51.100.10',
            'HTTP_CF_CONNECTING_IP' => '203.0.113.10',
        ],
    );

    expect(ResolveLoginAuditIpAddressAction::run($request))->toBeNull();
});
