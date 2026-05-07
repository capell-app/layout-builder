<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\PublishingStudio\Actions\DashboardReports\BuildContentSchedulerEventsAction;
use Capell\PublishingStudio\Filament\Pages\Tables\ScheduledPublishingTable;
use Capell\PublishingStudio\Filament\Widgets\ContentSchedulerCalendarWidget;
use Capell\PublishingStudio\Filament\Widgets\ContentSchedulerOverviewWidget;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class ScheduledPublishingPage extends Page implements HasActions, HasTable
{
    use HasNavigationBadge;
    use HasPageShield;
    use InteractsWithActions;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::CalendarDays;

    protected string $view = 'capell-admin::components.pages.table';

    protected static ?string $slug = 'scheduled-publishing';

    protected static ?int $navigationSort = 1;

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) __('capell-publishing-studio::scheduler.navigation.label');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-admin::navigation.group_workflow');
    }

    #[Override]
    public static function getNavigationBadge(): ?string
    {
        $count = BuildContentSchedulerEventsAction::run()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return ScheduledPublishingTable::configure($table);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-publishing-studio::scheduler.subheading');
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return __('capell-publishing-studio::scheduler.title');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ContentSchedulerOverviewWidget::class,
            ContentSchedulerCalendarWidget::class,
        ];
    }
}
