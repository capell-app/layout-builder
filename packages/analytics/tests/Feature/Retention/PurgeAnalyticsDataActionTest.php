<?php

declare(strict_types=1);

use Capell\Analytics\Actions\PurgeAnalyticsDataAction;
use Capell\Analytics\Enums\AnalyticsEventType;
use Capell\Analytics\Models\AnalyticsConsent;
use Capell\Analytics\Models\AnalyticsEvent;
use Capell\Analytics\Models\AnalyticsVisit;
use Capell\Analytics\Tests\AnalyticsTestCase;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

uses(AnalyticsTestCase::class);

it('purges analytics events consents and eligible visits older than retention', function (): void {
    $oldTimestamp = CarbonImmutable::parse('2025-01-01 00:00:00');
    $recentTimestamp = CarbonImmutable::parse('2026-04-01 00:00:00');
    $oldVisit = AnalyticsVisit::factory()->create([
        'started_at' => $oldTimestamp,
        'last_seen_at' => $oldTimestamp,
    ]);
    $oldVisitWithRecentEvent = AnalyticsVisit::factory()->create([
        'started_at' => $oldTimestamp,
        'last_seen_at' => $oldTimestamp,
    ]);
    $recentVisit = AnalyticsVisit::factory()->create([
        'started_at' => $recentTimestamp,
        'last_seen_at' => $recentTimestamp,
    ]);

    $oldEvent = AnalyticsEvent::factory()->create([
        'visit_id' => $oldVisit->getKey(),
        'type' => AnalyticsEventType::PageView,
        'occurred_at' => $oldTimestamp,
    ]);
    $recentEvent = AnalyticsEvent::factory()->create([
        'visit_id' => $oldVisitWithRecentEvent->getKey(),
        'type' => AnalyticsEventType::PageView,
        'occurred_at' => $recentTimestamp,
    ]);
    $oldConsent = AnalyticsConsent::factory()->create([
        'visit_id' => $oldVisit->getKey(),
        'decided_at' => $oldTimestamp,
    ]);
    $recentConsent = AnalyticsConsent::factory()->create([
        'visit_id' => $recentVisit->getKey(),
        'decided_at' => $recentTimestamp,
    ]);

    $deletedRecords = PurgeAnalyticsDataAction::run(90);

    expect($deletedRecords)->toBe(3)
        ->and(AnalyticsEvent::query()->whereKey($oldEvent->getKey())->exists())->toBeFalse()
        ->and(AnalyticsConsent::query()->whereKey($oldConsent->getKey())->exists())->toBeFalse()
        ->and(AnalyticsVisit::query()->whereKey($oldVisit->getKey())->exists())->toBeFalse()
        ->and(AnalyticsEvent::query()->whereKey($recentEvent->getKey())->exists())->toBeTrue()
        ->and(AnalyticsConsent::query()->whereKey($recentConsent->getKey())->exists())->toBeTrue()
        ->and(AnalyticsVisit::query()->whereKey($oldVisitWithRecentEvent->getKey())->exists())->toBeTrue()
        ->and(AnalyticsVisit::query()->whereKey($recentVisit->getKey())->exists())->toBeTrue();
});

it('uses configured retention days when no override is provided', function (): void {
    config()->set('capell-analytics.retention_days', 30);

    $oldVisit = AnalyticsVisit::factory()->create([
        'started_at' => now()->subDays(45)->toImmutable(),
        'last_seen_at' => now()->subDays(45)->toImmutable(),
    ]);
    $recentVisit = AnalyticsVisit::factory()->create([
        'started_at' => now()->subDays(20)->toImmutable(),
        'last_seen_at' => now()->subDays(20)->toImmutable(),
    ]);

    PurgeAnalyticsDataAction::run();

    expect(AnalyticsVisit::query()->whereKey($oldVisit->getKey())->exists())->toBeFalse()
        ->and(AnalyticsVisit::query()->whereKey($recentVisit->getKey())->exists())->toBeTrue();
});

it('rejects invalid purge command retention days before deleting records', function (string $daysOption): void {
    $oldVisit = AnalyticsVisit::factory()->create([
        'started_at' => now()->subDays(45)->toImmutable(),
        'last_seen_at' => now()->subDays(45)->toImmutable(),
    ]);

    $this->artisan('analytics:purge', ['--days' => $daysOption])
        ->expectsOutput('The --days option must be a positive integer.')
        ->assertExitCode(Command::FAILURE);

    expect(AnalyticsVisit::query()->whereKey($oldVisit->getKey())->exists())->toBeTrue();
})->with(['abc', '-1', '0']);
