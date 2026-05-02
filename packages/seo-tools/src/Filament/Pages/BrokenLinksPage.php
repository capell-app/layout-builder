<?php

declare(strict_types=1);

namespace Capell\SeoTools\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\SeoTools\Filament\Pages\Tables\BrokenLinksTable;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class BrokenLinksPage extends Page implements HasActions, HasTable
{
    use HasNavigationBadge;
    use HasPageShield;
    use InteractsWithActions;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::ExclamationTriangle;

    protected string $view = 'capell-admin::components.pages.table';

    protected static ?string $slug = 'broken-links';

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-admin::navigation.broken_links'));
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_monitoring'));
    }

    public static function table(Table $table): Table
    {
        return BrokenLinksTable::configure($table);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-admin::generic.broken_links_info');
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return __('capell-admin::heading.broken_links');
    }
}
