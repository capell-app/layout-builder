<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\DashboardReports\Actions\Dashboard\BuildDefaultContentHealthAction;
use Capell\DashboardReports\Tests\DashboardReportsTestCase;

uses(DashboardReportsTestCase::class);

it('builds default CMS content health issues from core pages', function (): void {
    Page::factory()->pending()->create();
    Page::factory()->expired()->create();

    $data = BuildDefaultContentHealthAction::run();
    $issues = collect($data->issues->toArray())->keyBy('id');

    expect($issues->get('scheduled_pages')['count'])->toBe(1)
        ->and($issues->get('expired_pages')['count'])->toBe(1)
        ->and($issues->get('pages_without_urls')['count'])->toBe(2)
        ->and($issues->get('scheduled_pages')['label'])->toBe(__('capell-dashboard-reports::dashboard.issue_scheduled_pages'));
});
