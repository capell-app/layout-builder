<?php

declare(strict_types=1);

use Capell\Insights\Actions\PurgeInsightsDataAction;
use Capell\Insights\Enums\InsightsEventType;
use Capell\Insights\Models\InsightsConsent;
use Capell\Insights\Models\InsightsEvent;
use Capell\Insights\Models\InsightsVisit;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

it('purges insights events consents and eligible visits older than retention', function (): void {
    $oldTimestamp = CarbonImmutable::parse('2025-01-01 00:00:00');
    $recentTimestamp = CarbonImmutable::parse('2026-04-01 00:00:00');
    $oldVisit = InsightsVisit::factory()->create([
        'started_at' => $oldTimestamp,
        'last_seen_at' => $oldTimestamp,
    ]);
    $oldVisitWithRecentEvent = InsightsVisit::factory()->create([
        'started_at' => $oldTimestamp,
        'last_seen_at' => $oldTimestamp,
    ]);
    $recentVisit = InsightsVisit::factory()->create([
        'started_at' => $recentTimestamp,
        'last_seen_at' => $recentTimestamp,
    ]);

    $oldEvent = InsightsEvent::factory()->create([
        'visit_id' => $oldVisit->getKey(),
        'type' => InsightsEventType::PageView,
        'occurred_at' => $oldTimestamp,
    ]);
    $recentEvent = InsightsEvent::factory()->create([
        'visit_id' => $oldVisitWithRecentEvent->getKey(),
        'type' => InsightsEventType::PageView,
        'occurred_at' => $recentTimestamp,
    ]);
    $oldConsent = InsightsConsent::factory()->create([
        'visit_id' => $oldVisit->getKey(),
        'decided_at' => $oldTimestamp,
    ]);
    $recentConsent = InsightsConsent::factory()->create([
        'visit_id' => $recentVisit->getKey(),
        'decided_at' => $recentTimestamp,
    ]);

    $deletedRecords = PurgeInsightsDataAction::run(90);

    expect($deletedRecords)->toBe(3)
        ->and(InsightsEvent::query()->whereKey($oldEvent->getKey())->exists())->toBeFalse()
        ->and(InsightsConsent::query()->whereKey($oldConsent->getKey())->exists())->toBeFalse()
        ->and(InsightsVisit::query()->whereKey($oldVisit->getKey())->exists())->toBeFalse()
        ->and(InsightsEvent::query()->whereKey($recentEvent->getKey())->exists())->toBeTrue()
        ->and(InsightsConsent::query()->whereKey($recentConsent->getKey())->exists())->toBeTrue()
        ->and(InsightsVisit::query()->whereKey($oldVisitWithRecentEvent->getKey())->exists())->toBeTrue()
        ->and(InsightsVisit::query()->whereKey($recentVisit->getKey())->exists())->toBeTrue();
});

it('uses configured retention days when no override is provided', function (): void {
    config()->set('capell-insights.retention_days', 30);

    $oldVisit = InsightsVisit::factory()->create([
        'started_at' => now()->subDays(45)->toImmutable(),
        'last_seen_at' => now()->subDays(45)->toImmutable(),
    ]);
    $recentVisit = InsightsVisit::factory()->create([
        'started_at' => now()->subDays(20)->toImmutable(),
        'last_seen_at' => now()->subDays(20)->toImmutable(),
    ]);

    PurgeInsightsDataAction::run();

    expect(InsightsVisit::query()->whereKey($oldVisit->getKey())->exists())->toBeFalse()
        ->and(InsightsVisit::query()->whereKey($recentVisit->getKey())->exists())->toBeTrue();
});

it('rejects invalid purge command retention days before deleting records', function (string $daysOption): void {
    $oldVisit = InsightsVisit::factory()->create([
        'started_at' => now()->subDays(45)->toImmutable(),
        'last_seen_at' => now()->subDays(45)->toImmutable(),
    ]);

    $this->artisan('insights:purge', ['--days' => $daysOption])
        ->expectsOutput('The --days option must be a positive integer.')
        ->assertExitCode(Command::FAILURE);

    expect(InsightsVisit::query()->whereKey($oldVisit->getKey())->exists())->toBeTrue();
})->with(['abc', '-1', '0']);
