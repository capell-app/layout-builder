<?php

declare(strict_types=1);

use Capell\Analytics\Actions\BuildJourneyTimelineAction;
use Capell\Analytics\Actions\BuildPopularPagesQueryAction;
use Capell\Analytics\Actions\BuildRecentJourneysQueryAction;
use Capell\Analytics\Actions\BuildTopActionsQueryAction;
use Capell\Analytics\Actions\BuildTrendingPagesQueryAction;
use Capell\Analytics\Data\AnalyticsWindowData;
use Capell\Analytics\Enums\AnalyticsEventType;
use Capell\Analytics\Models\AnalyticsEvent;
use Capell\Analytics\Models\AnalyticsVisit;
use Capell\Analytics\Tests\AnalyticsTestCase;
use Carbon\CarbonImmutable;

uses(AnalyticsTestCase::class);

it('sorts popular pages by page view count descending', function (): void {
    $window = analyticsReportWindow();
    $firstVisit = AnalyticsVisit::factory()->create();
    $secondVisit = AnalyticsVisit::factory()->create();

    analyticsReportEvent($firstVisit, AnalyticsEventType::PageView, '/popular', 'https://example.test/popular', $window->startsAt->addHour());
    analyticsReportEvent($secondVisit, AnalyticsEventType::PageView, '/popular', 'https://example.test/popular', $window->startsAt->addHours(2));
    analyticsReportEvent($firstVisit, AnalyticsEventType::Click, '/popular', 'https://example.test/popular', $window->startsAt->addHours(3));
    analyticsReportEvent($firstVisit, AnalyticsEventType::PageView, '/quiet', 'https://example.test/quiet', $window->startsAt->addHours(4));
    analyticsReportEvent($firstVisit, AnalyticsEventType::PageView, '/outside', 'https://example.test/outside', $window->startsAt->subDay());

    $summaries = BuildPopularPagesQueryAction::run($window);

    expect($summaries->pluck('path')->all())->toBe(['/popular', '/quiet'])
        ->and($summaries->first())->toMatchArray([
            'path' => '/popular',
            'url' => 'https://example.test/popular',
            'page_views' => 2,
            'unique_visits' => 2,
            'clicks' => 1,
        ]);
});

it('compares trending pages with the previous equivalent window', function (): void {
    $window = analyticsReportWindow();
    $currentVisit = AnalyticsVisit::factory()->create();
    $previousVisit = AnalyticsVisit::factory()->create();

    analyticsReportEvent($currentVisit, AnalyticsEventType::PageView, '/rising', 'https://example.test/rising', $window->startsAt->addHour());
    analyticsReportEvent($currentVisit, AnalyticsEventType::PageView, '/rising', 'https://example.test/rising', $window->startsAt->addHours(2));
    analyticsReportEvent($previousVisit, AnalyticsEventType::PageView, '/rising', 'https://example.test/rising', $window->startsAt->subHour());
    analyticsReportEvent($currentVisit, AnalyticsEventType::PageView, '/new', 'https://example.test/new', $window->startsAt->addHours(3));

    $summaries = BuildTrendingPagesQueryAction::run($window);

    expect($summaries->pluck('path')->all())->toBe(['/rising', '/new']);

    expect($summaries->firstWhere('path', '/rising'))->toMatchArray([
        'current_page_views' => 2,
        'previous_page_views' => 1,
        'change' => 1,
        'change_percentage' => 100.0,
    ]);

    expect($summaries->firstWhere('path', '/new'))->toMatchArray([
        'current_page_views' => 1,
        'previous_page_views' => 0,
        'change' => 1,
        'change_percentage' => 100.0,
    ]);
});

it('builds an ordered journey timeline with seconds since previous step', function (): void {
    $visit = AnalyticsVisit::factory()->create();
    $startedAt = CarbonImmutable::parse('2026-04-30 10:00:00');

    analyticsReportEvent($visit, AnalyticsEventType::Click, '/pricing', 'https://example.test/pricing', $startedAt->addSeconds(45), sequence: 2);
    analyticsReportEvent($visit, AnalyticsEventType::PageView, '/', 'https://example.test/', $startedAt, sequence: 1);
    analyticsReportEvent($visit, AnalyticsEventType::Custom, '/pricing', 'https://example.test/pricing', $startedAt->addSeconds(75), sequence: 3, eventName: 'signup_started');

    $steps = BuildJourneyTimelineAction::run($visit);

    expect($steps->pluck('sequence')->all())->toBe([1, 2, 3])
        ->and($steps[0]->secondsSincePreviousStep)->toBeNull()
        ->and($steps[1]->secondsSincePreviousStep)->toBe(45)
        ->and($steps[2]->secondsSincePreviousStep)->toBe(30)
        ->and($steps[2]->eventName)->toBe('signup_started');
});

it('returns recent journeys ordered by last seen date', function (): void {
    $olderVisit = AnalyticsVisit::factory()->create([
        'last_seen_at' => CarbonImmutable::parse('2026-04-20 10:00:00'),
    ]);
    $recentVisit = AnalyticsVisit::factory()->create([
        'last_seen_at' => CarbonImmutable::parse('2026-04-21 10:00:00'),
    ]);
    $emptyVisit = AnalyticsVisit::factory()->create([
        'last_seen_at' => CarbonImmutable::parse('2026-04-22 10:00:00'),
    ]);

    analyticsReportEvent($olderVisit, AnalyticsEventType::PageView, '/older', 'https://example.test/older', CarbonImmutable::parse('2026-04-20 10:00:00'));
    analyticsReportEvent($recentVisit, AnalyticsEventType::PageView, '/recent', 'https://example.test/recent', CarbonImmutable::parse('2026-04-21 10:00:00'), sequence: 1);
    analyticsReportEvent($recentVisit, AnalyticsEventType::Click, '/recent/contact', 'https://example.test/recent/contact', CarbonImmutable::parse('2026-04-21 10:01:00'), sequence: 2);

    $journeys = BuildRecentJourneysQueryAction::run();

    expect($journeys->pluck('visit')->all())->toBe([$recentVisit->uuid, $olderVisit->uuid])
        ->and($journeys->pluck('visit')->all())->not->toContain($emptyVisit->uuid)
        ->and($journeys->first())->toMatchArray([
            'visit' => $recentVisit->uuid,
            'steps' => 2,
            'last_path' => '/recent/contact',
        ]);
});

it('groups top actions in the current window and excludes page views', function (): void {
    $window = analyticsReportWindow();
    $visit = AnalyticsVisit::factory()->create();

    analyticsReportEvent($visit, AnalyticsEventType::Custom, '/pricing', 'https://example.test/pricing', $window->startsAt->addHour(), eventName: 'signup_started');
    analyticsReportEvent($visit, AnalyticsEventType::Custom, '/pricing', 'https://example.test/pricing', $window->startsAt->addHours(2), eventName: 'signup_started');
    analyticsReportEvent($visit, AnalyticsEventType::Click, '/pricing', 'https://example.test/pricing', $window->startsAt->addHours(3), label: 'Pricing CTA', location: 'hero');
    analyticsReportEvent($visit, AnalyticsEventType::PageView, '/pricing', 'https://example.test/pricing', $window->startsAt->addHours(4));
    analyticsReportEvent($visit, AnalyticsEventType::Custom, '/outside', 'https://example.test/outside', $window->startsAt->subDay(), eventName: 'outside_window');

    $actions = BuildTopActionsQueryAction::run($window);

    expect($actions->pluck('action')->all())->toBe(['signup_started', 'Pricing CTA'])
        ->and($actions->first())->toMatchArray([
            'action' => 'signup_started',
            'event_name' => 'signup_started',
            'events' => 2,
        ])
        ->and($actions->last())->toMatchArray([
            'action' => 'Pricing CTA',
            'label' => 'Pricing CTA',
            'location' => 'hero',
            'events' => 1,
        ]);
});

function analyticsReportWindow(): AnalyticsWindowData
{
    return new AnalyticsWindowData(
        startsAt: CarbonImmutable::parse('2026-04-20 00:00:00'),
        endsAt: CarbonImmutable::parse('2026-04-27 00:00:00'),
    );
}

function analyticsReportEvent(
    AnalyticsVisit $visit,
    AnalyticsEventType $type,
    string $path,
    string $url,
    CarbonImmutable $occurredAt,
    int $sequence = 1,
    ?string $eventName = null,
    ?string $label = null,
    ?string $location = null,
): AnalyticsEvent {
    return AnalyticsEvent::factory()->create([
        'visit_id' => $visit->getKey(),
        'type' => $type,
        'path' => $path,
        'url' => $url,
        'occurred_at' => $occurredAt,
        'sequence' => $sequence,
        'event_name' => $eventName,
        'label' => $label,
        'location' => $location,
    ]);
}
