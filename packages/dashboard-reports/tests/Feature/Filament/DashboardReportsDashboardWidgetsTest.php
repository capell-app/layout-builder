<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Dashboard\ContentHealthDataProvider;
use Capell\Admin\Data\Dashboard\ContentHealthData;
use Capell\Admin\Data\Dashboard\ContentHealthIssueData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Models\Page;
use Capell\DashboardReports\Filament\Widgets\ContentHealthWidget;
use Capell\DashboardReports\Filament\Widgets\PublishingTrendChartWidget;
use Capell\DashboardReports\Providers\AdminServiceProvider;
use Capell\DashboardReports\Support\Dashboard\DashboardReportsContentHealthDataProvider;
use Capell\DashboardReports\Tests\DashboardReportsTestCase;
use Spatie\LaravelData\DataCollection;

uses(DashboardReportsTestCase::class);

it('registers dashboard-dashboard_reports dashboard widgets on the main dashboard', function (): void {
    expect(CapellAdmin::getDashboardWidgets(DashboardEnum::Main))
        ->toContain(PublishingTrendChartWidget::class)
        ->toContain(ContentHealthWidget::class);
});

it('binds dashboard-dashboard_reports content health as the installed content health provider', function (): void {
    expect(resolve(ContentHealthDataProvider::class))
        ->toBeInstanceOf(DashboardReportsContentHealthDataProvider::class);
});

it('does not replace another package content health provider', function (): void {
    $externalContentHealthDataProvider = new class implements ContentHealthDataProvider
    {
        public function build(): ContentHealthData
        {
            return ContentHealthData::from([
                'missingMetaDescriptionCount' => 0,
                'duplicateTitleCount' => 0,
                'staleContentCount' => 0,
                'emptyContentCount' => 0,
            ]);
        }
    };

    app()->instance(ContentHealthDataProvider::class, $externalContentHealthDataProvider);

    $method = new ReflectionMethod(AdminServiceProvider::class, 'registerDashboardDataProviders');
    $method->invoke(new AdminServiceProvider(app()));

    expect(resolve(ContentHealthDataProvider::class))->toBe($externalContentHealthDataProvider);
});

it('uses dashboard-dashboard_reports-owned translations and views for dashboard-dashboard_reports widgets', function (): void {
    $contentHealthWidget = new ContentHealthWidget;
    $contentHealthView = (fn (): string => $this->view)->call($contentHealthWidget);

    expect((new PublishingTrendChartWidget)->getHeading())->toBe(__('capell-dashboard-reports::dashboard.widget_publishing_trend'))
        ->and($contentHealthView)->toBe('capell-dashboard-reports::widgets.content-health');
});

it('builds content health data through the installed provider and widget data contract', function (): void {
    Page::factory()->pending()->create();

    $providerData = resolve(ContentHealthDataProvider::class)->build();

    $widgetContentHealthDataProvider = new class implements ContentHealthDataProvider
    {
        public function build(): ContentHealthData
        {
            return new ContentHealthData(
                issues: ContentHealthIssueData::collect([
                    new ContentHealthIssueData(
                        id: 'custom_issue',
                        label: 'Custom issue',
                        count: 2,
                        filterUrl: '/admin/pages',
                    ),
                ], DataCollection::class),
            );
        }
    };

    app()->instance(ContentHealthDataProvider::class, $widgetContentHealthDataProvider);
    $widgetData = (new ContentHealthWidget)->data();

    $providerIssues = collect($providerData->issues->toArray())->keyBy('id');

    expect($providerIssues)->toHaveKey('scheduled_pages')
        ->and($widgetData->issues->first()->id)->toBe('custom_issue')
        ->and($widgetData->issues->first()->count)->toBe(2);
});
