<?php

declare(strict_types=1);

namespace Capell\Plugins\Filament\Pages\Plugins\Tables;

use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Plugins\Actions\InstallPluginAction;
use Capell\Plugins\Actions\UninstallPluginAction;
use Capell\Plugins\Actions\UpdatePluginAction;
use Capell\Plugins\Filament\Pages\PluginsPage;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Capell\Plugins\Support\SiteIdResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Throwable;

#[On('refresh-table')]
class PluginsTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(static::getQuery())
            ->columns(static::getTableColumns())
            ->emptyStateDescription(function (PluginsPage $livewire): ?string {
                if ($livewire->isBrowseTab()) {
                    return __('capell-plugins::table.no_plugins_browse');
                }

                if ($livewire->isInstalledTab()) {
                    return __('capell-plugins::table.no_plugins_installed');
                }

                return __('capell-plugins::table.all_up_to_date');
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
                return $query->installed()
                    ->distinct()
                    ->orderBy('name');
            }

            // Updates tab: plugins with available updates
            return $query->whereHas('licenses')
                ->where('has_update', true)
                ->orderBy('name');
        };
    }

    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label(__('capell-plugins::table.name'))
                ->description(fn (MarketplacePlugin $record): ?string => $record->description)
                ->weight(FontWeight::Medium)
                ->sortable()
                ->searchable()
                ->wrap(),
            TextColumn::make('vendor')
                ->label(__('capell-plugins::table.vendor'))
                ->sortable()
                ->searchable(),
            TextColumn::make('kind')
                ->label(__('capell-plugins::table.type'))
                ->badge()
                ->sortable(),
            TextColumn::make('license_model')
                ->label(__('capell-plugins::table.license'))
                ->badge()
                ->sortable(),
            TextColumn::make('activeLicense.status')
                ->label(__('capell-plugins::table.status'))
                ->badge()
                ->visible(fn (PluginsPage $livewire): bool => $livewire->isInstalledTab())
                ->formatStateUsing(fn (?string $state): string => $state ?? __('capell-plugins::table.not_licensed'))
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
            ->modalHeading(fn (MarketplacePlugin $record): string => __('Install :plugin', ['plugin' => $record->name]))
            ->action(function (PluginsPage $livewire, Action $action, MarketplacePlugin $record, array $data): void {
                try {
                    $licenseKey = isset($data['license_key']) && is_string($data['license_key'])
                        ? $data['license_key']
                        : null;

                    $isPaid = $record->price_once !== null
                        || $record->price_monthly !== null
                        || $record->price_yearly !== null;

                    // Paid plugins need a site id so anystack can track the
                    // activation. We derive it from APP_KEY so it's stable
                    // across requests without needing a new settings row or
                    // UI field — see SiteIdResolver for the trade-offs.
                    // Fingerprint stays null for this iteration: a future
                    // revision can derive one if anystack policies require
                    // it.
                    $siteId = $isPaid ? SiteIdResolver::get() : null;

                    // Capability warnings can be previewed via
                    // InstallPluginAction::previewCapabilityWarnings($record).
                    // Wiring a confirmation modal for Red-level warnings is a
                    // separate feature and will land in a follow-up PR.
                    InstallPluginAction::run($record, $licenseKey, $siteId);

                    $action->success();
                    $livewire->dispatch('refresh-table');
                } catch (Throwable $throwable) {
                    $action->failureNotificationTitle(__('Installation Failed'));
                    $action->failureNotificationBody($throwable->getMessage());
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
            ->modalHeading(fn (MarketplacePlugin $record): string => __('Uninstall :plugin', ['plugin' => $record->name]))
            ->modalDescription(__('This action cannot be undone. Are you sure?'))
            ->modalSubmitActionLabel(__('Uninstall'))
            ->action(function (PluginsPage $livewire, Action $action, MarketplacePlugin $record): void {
                try {
                    UninstallPluginAction::run($record);
                    $action->success();
                    $livewire->dispatch('refresh-table');
                } catch (Throwable $throwable) {
                    $action->failureNotificationTitle(__('Uninstallation Failed'));
                    $action->failureNotificationBody($throwable->getMessage());
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
                } catch (Throwable $throwable) {
                    $action->failureNotificationTitle(__('Update Failed'));
                    $action->failureNotificationBody($throwable->getMessage());
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
                && ! $record->activeLicense() instanceof MarketplacePluginLicense)
            ->url(fn (MarketplacePlugin $record): ?string => $record->getAttribute('purchase_url'), shouldOpenInNewTab: true);
    }
}
