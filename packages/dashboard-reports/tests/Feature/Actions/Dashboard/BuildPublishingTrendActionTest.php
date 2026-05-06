<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\DashboardReports\Actions\Dashboard\BuildPublishingTrendAction;
use Capell\DashboardReports\Tests\DashboardReportsTestCase;
use Carbon\CarbonImmutable;

uses(DashboardReportsTestCase::class);

it('builds a publishing trend series for the selected dashboard period', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-03 12:00:00'));

    Page::factory()->published(CarbonImmutable::parse('2026-05-01 09:00:00'))->create();
    Page::factory()->published(CarbonImmutable::parse('2026-05-02 09:00:00'))->create();
    Page::factory()->pending()->create();

    $data = BuildPublishingTrendAction::run('this_week');

    expect($data->points)->toHaveCount(7)
        ->and($data->totalPublished)->toBe(2)
        ->and($data->totalScheduled)->toBe(1);
});
