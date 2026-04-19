<?php

declare(strict_types=1);

namespace Capell\Plugins\Filament\Pages;

use BackedEnum;
use Capell\Plugins\Filament\Pages\Plugins\Tables\PluginsTable;
use Capell\Plugins\Models\MarketplacePlugin;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;
use Override;

class PluginsPage extends Page implements HasActions, HasTable
{
    use HasTabs;
    use InteractsWithActions;
    use InteractsWithTable {
        makeTable as makeBaseTable;
    }

    #[Url(as: 'tab')]
    public ?string $activeTab = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::PuzzlePiece;

    protected static ?string $slug = 'marketplace-plugins';

    protected static ?int $navigationSort = 8;

    private static string $browseTab = 'browse';

    private static string $installedTab = 'installed';

    private static string $updatesTab = 'updates';

    public static function getModel(): string
    {
        return MarketplacePlugin::class;
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) __('Marketplace');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) __('Plugins');
    }

    public static function table(Table $table): Table
    {
        return PluginsTable::configure($table);
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return __('Plugin Marketplace');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('Browse, install, and manage marketplace plugins');
    }

    public function getTabs(): array
    {
        return [
            self::$browseTab => Tab::make()
                ->label(__('Browse'))
                ->badge(function (): ?int {
                    $count = MarketplacePlugin::where('is_visible', true)->count();

                    return $count > 0 ? $count : null;
                }),
            self::$installedTab => Tab::make()
                ->label(__('Installed'))
                ->badge(function (): ?int {
                    $count = MarketplacePlugin::query()
                        ->installed()
                        ->distinct()
                        ->count();

                    return $count > 0 ? $count : null;
                }),
            self::$updatesTab => Tab::make()
                ->label(__('Updates'))
                ->badge(function (): ?int {
                    // TODO: Implement available updates check
                    return null;
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getTabsContentComponent(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
    }

    public function getActiveTab(): ?string
    {
        return $this->activeTab ?? self::$browseTab;
    }

    public function isBrowseTab(): bool
    {
        return $this->getActiveTab() === self::$browseTab;
    }

    public function isInstalledTab(): bool
    {
        return $this->getActiveTab() === self::$installedTab;
    }

    public function isUpdatesTab(): bool
    {
        return $this->getActiveTab() === self::$updatesTab;
    }
}
