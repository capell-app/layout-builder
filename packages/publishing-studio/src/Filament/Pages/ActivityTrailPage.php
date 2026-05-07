<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Pages;

use BackedEnum;
use Capell\PublishingStudio\Filament\Pages\Tables\ActivityTrailTable;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ActivityTrailPage extends Page implements HasActions, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $slug = 'dashboard-dashboard_reports/activity-trail';

    protected static ?string $title = 'Activity Trail';

    protected static ?int $navigationSort = 1;

    protected string $view = 'capell-admin::components.pages.table';

    public static function getNavigationLabel(): string
    {
        return (string) __('capell-admin::navigation.activity_trail');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:' . class_basename(static::class)) ?? false;
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_administration'));
    }

    public function table(Table $table): Table
    {
        return ActivityTrailTable::configure($table);
    }
}
