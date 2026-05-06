<?php

declare(strict_types=1);

namespace Capell\DashboardReports\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Contracts\Dashboard\ContentHealthDataProvider;
use Capell\Admin\Data\Dashboard\ContentHealthData;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

final class ContentHealthWidget extends Widget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['editor', 'admin', 'super_admin'];

    protected static string $settingsKey = 'content_health';

    protected string $view = 'capell-dashboard-reports::widgets.content-health';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return self::canViewCheck() && self::hasContentHealthData();
    }

    #[Computed(persist: true, seconds: 300)]
    public function data(): ContentHealthData
    {
        return resolve(ContentHealthDataProvider::class)->build();
    }

    private static function hasContentHealthData(): bool
    {
        return resolve(ContentHealthDataProvider::class)
            ->build()
            ->issues
            ->count() > 0;
    }
}
