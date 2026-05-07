<?php

declare(strict_types=1);

namespace Capell\Events\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Capell\Events\Filament\Widgets\EventCalendarWidget;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class EventCalendarPage extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::CalendarDays;

    protected static ?string $slug = 'events-calendar';

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) __('capell-events::generic.admin_calendar');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-events::generic.events');
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return __('capell-events::generic.admin_calendar');
    }

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-events::generic.admin_calendar_subheading');
    }

    /**
     * @return list<class-string>
     */
    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            EventCalendarWidget::class,
        ];
    }
}
