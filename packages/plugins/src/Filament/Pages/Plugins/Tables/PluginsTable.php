<?php

declare(strict_types=1);

namespace Capell\Plugins\Filament\Pages\Plugins\Tables;

use Capell\Plugins\Actions\InstallPluginAction;
use Capell\Plugins\Actions\UninstallPluginAction;
use Capell\Plugins\Actions\UpdatePluginAction;
use Capell\Plugins\Filament\Pages\PluginsPage;
use Capell\Plugins\Models\MarketplacePlugin;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Throwable;

#[On('refresh-table')]
class PluginsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(static::getQuery())
            ->columns(static::getTableColumns())
            ->emptyStateDescription(function (PluginsPage $livewire): ?string {
                if ($livewire->isBrowseTab()) {
                    return __('No plugins available in the marketplace');
                }

                if ($livewire->isInstalledTab()) {
                    return __('No plugins installed yet');
                }

                return __('All plugins are up to date');
            })
            ->recordActions([
                self::installAction(),
                self::uninstallAction(),
                self::updateAction(),
                self::buyAction(),
            ]);
    }

    protected static function getQuery()
    {
        return function (PluginsPage $livewire): Builder {
            $query = MarketplacePlugin::query();

            if ($livewire->isBrowseTab()) {
                return $query->where('is_visible', true)->orderBy('sort_order');
            }

            if ($livewire->isInstalledTab()) {
                return $query->whereHas('licenses')
                    ->orWhere(function ($q): void {
                        foreach (explode(',', 'mosaic,blog,address,assistant') as $installed) {
                            $q->orWhere('composer_name', 'capell-app/' . trim($installed));
                        }
                    })
                    ->distinct()
                    ->orderBy('title');
            }

            // Updates tab: plugins with available updates
            return $query->whereHas('licenses')
                ->where('has_update', true)
                ->orderBy('title');
        };
    }

    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('title')
                ->label(__('Plugin'))
                ->description(fn (MarketplacePlugin $record): ?string => $record->description)
                ->weight(FontWeight::Medium)
                ->sortable()
                ->searchable()
                ->wrap(),
            TextColumn::make('vendor')
                ->label(__('Vendor'))
                ->sortable()
                ->searchable(),
            TextColumn::make('kind')
                ->label(__('Type'))
                ->badge()
                ->sortable(),
            TextColumn::make('license_model')
                ->label(__('License'))
                ->badge()
                ->sortable(),
            BadgeColumn::make('activeLicense.status')
                ->label(__('Status'))
                ->visible(fn (PluginsPage $livewire): bool => $livewire->isInstalledTab())
                ->formatStateUsing(fn (?string $state): string => $state ?? 'Not Licensed')
                ->getStateUsing(function (MarketplacePlugin $record): ?string {
                    $license = $record->activeLicense();

                    return $license?->status;
                }),
        ];
    }

    private static function installAction(): Action
    {
        return Action::make('install')
            ->label(__('Install'))
            ->button()
            ->color('success')
            ->icon(Heroicon::OutlinedCloudArrowDown)
            ->visible(fn (PluginsPage $livewire, MarketplacePlugin $record): bool => $livewire->isBrowseTab() && ! $record->isInstalled())
            ->schema(function (MarketplacePlugin $record): array {
                $schema = [];

                // For paid plugins, ask for license key first
                if ($record->price_once !== null || $record->price_monthly !== null || $record->price_yearly !== null) {
                    $schema[] = TextInput::make('license_key')
                        ->label(__('License Key'))
                        ->password()
                        ->required()
                        ->helperText(__('Enter your license key to activate this plugin'));
                }

                return $schema;
            })
            ->modalHeading(fn (MarketplacePlugin $record): string => __('Install :plugin', ['plugin' => $record->title]))
            ->action(function (PluginsPage $livewire, Action $action, MarketplacePlugin $record, array $data): void {
                try {
                    $licenseKey = $data['license_key'] ?? null;

                    // Preview capability warnings
                    $warnings = InstallPluginAction::run($record, $licenseKey);

                    // TODO: Show capability warnings modal with confirmation
                    // If Red level warnings exist, require explicit checkbox confirmation

                    $action->success();
                    $livewire->dispatch('refresh-table');
                } catch (Throwable $exception) {
                    $action->failureNotificationTitle(__('Installation Failed'));
                    $action->failureNotificationBody($exception->getMessage());
                    $action->failure();
                }
            });
    }

    private static function uninstallAction(): Action
    {
        return Action::make('uninstall')
            ->label(__('Uninstall'))
            ->button()
            ->outlined()
            ->color('danger')
            ->icon(Heroicon::OutlinedTrash)
            ->visible(fn (PluginsPage $livewire, MarketplacePlugin $record): bool => $livewire->isInstalledTab() && $record->isInstalled())
            ->requiresConfirmation()
            ->modalHeading(fn (MarketplacePlugin $record): string => __('Uninstall :plugin', ['plugin' => $record->title]))
            ->modalDescription(__('This action cannot be undone. Are you sure?'))
            ->modalSubmitActionLabel(__('Uninstall'))
            ->action(function (PluginsPage $livewire, Action $action, MarketplacePlugin $record): void {
                try {
                    UninstallPluginAction::run($record);
                    $action->success();
                    $livewire->dispatch('refresh-table');
                } catch (Throwable $exception) {
                    $action->failureNotificationTitle(__('Uninstallation Failed'));
                    $action->failureNotificationBody($exception->getMessage());
                    $action->failure();
                }
            });
    }

    private static function updateAction(): Action
    {
        return Action::make('update')
            ->label(__('Update'))
            ->button()
            ->color('warning')
            ->icon(Heroicon::OutlinedArrowPath)
            ->visible(fn (PluginsPage $livewire, MarketplacePlugin $record): bool => $livewire->isUpdatesTab())
            ->action(function (PluginsPage $livewire, Action $action, MarketplacePlugin $record): void {
                try {
                    UpdatePluginAction::run($record);
                    $action->success();
                    $livewire->dispatch('refresh-table');
                } catch (Throwable $exception) {
                    $action->failureNotificationTitle(__('Update Failed'));
                    $action->failureNotificationBody($exception->getMessage());
                    $action->failure();
                }
            });
    }

    private static function buyAction(): Action
    {
        return Action::make('buy')
            ->label(__('Buy'))
            ->link()
            ->icon(Heroicon::OutlinedCurrencyDollar)
            ->visible(fn (PluginsPage $livewire, MarketplacePlugin $record): bool => $livewire->isBrowseTab()
                && ($record->price_once !== null || $record->price_monthly !== null || $record->price_yearly !== null)
                && $record->activeLicense() === null)
            ->url(fn (MarketplacePlugin $record): ?string => $record->getAttribute('purchase_url'), shouldOpenInNewTab: true);
    }
}
