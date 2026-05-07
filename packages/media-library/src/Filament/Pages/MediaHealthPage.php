<?php

declare(strict_types=1);

namespace Capell\MediaLibrary\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\MediaLibrary\Filament\Pages\Tables\MediaHealthTable;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class MediaHealthPage extends Page implements HasActions, HasTable
{
    use HasNavigationBadge;
    use HasPageShield;
    use InteractsWithActions;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Photo;

    protected string $view = 'capell-admin::components.pages.table';

    protected static ?string $slug = 'media-health';

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-admin::navigation.media_health'));
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_administration'));
    }

    public static function table(Table $table): Table
    {
        return MediaHealthTable::configure($table);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-admin::generic.media_health_info');
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return __('capell-admin::heading.media_health');
    }
}
