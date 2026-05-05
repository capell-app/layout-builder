<?php

declare(strict_types=1);

namespace Capell\ExampleSites\Support\Extensions;

use Capell\Admin\Filament\Components\Forms\LanguageSelect;
use Capell\Admin\Filament\Components\Forms\SiteSelect;
use Capell\Admin\Filament\Pages\ExtensionsPage;
use Capell\Admin\Support\Extensions\ExtensionsPageActionRegistry;
use Capell\Core\Facades\CapellCore;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Throwable;

final class ExampleSitesExtensionsPageActions
{
    public function register(ExtensionsPageActionRegistry $registry): void
    {
        $registry->registerHeaderAction(fn (ExtensionsPage $page): Action => $this->installExampleSiteDataAction());
    }

    private function installExampleSiteDataAction(): Action
    {
        return Action::make('installExampleSiteData')
            ->label(__('capell-admin::button.demo_install'))
            ->icon(Heroicon::OutlinedBugAnt)
            ->outlined()
            ->size('sm')
            ->color('gray')
            ->authorize(fn (): bool => ExtensionsPage::canManageExtensions())
            ->visible(fn (): bool => $this->canInstallExampleSiteData())
            ->schema(fn (): array => $this->getFormSchemaForParams($this->getExampleSiteCommandParams()))
            ->modalHeading(__('capell-admin::generic.install_demo_data'))
            ->modalDescription(__('capell-admin::generic.install_demo_data_description'))
            ->successNotificationTitle(__('capell-admin::notification.plugin_demo_installed'))
            ->failureNotificationTitle(__('capell-admin::notification.plugin_demo_installation_failed'))
            ->action(function (ExtensionsPage $livewire, Action $action, array $data): void {
                foreach (CapellCore::getInstalledPackages() as $package) {
                    if ($package->getDemoCommand() === null) {
                        continue;
                    }

                    $exampleSiteParams = array_map(
                        fn (string $param): ?string => $data[$param] ?? null,
                        $package->getDemoParams(),
                    );

                    try {
                        Artisan::call($package->getDemoCommand(), $exampleSiteParams);
                    } catch (Throwable $throwable) {
                        $action->failureNotificationBody($throwable->getMessage());
                        $action->failure();

                        return;
                    }
                }

                $action->success();
                $livewire->dispatch('refresh-table');
            });
    }

    private function canInstallExampleSiteData(): bool
    {
        return ExtensionsPage::canManageExtensions()
            && config(
                'capell-admin.enable_demo_installation',
                app()->isLocal() && ! app()->isProduction(),
            );
    }

    /** @return array<int, string> */
    private function getExampleSiteCommandParams(): array
    {
        $exampleSiteParams = [];

        foreach (CapellCore::getInstalledPackages() as $package) {
            if ($package->getDemoCommand() === null || $package->getDemoParams() === []) {
                continue;
            }

            foreach ($package->getDemoParams() as $param) {
                if (is_string($param) && $param !== '' && ! in_array($param, $exampleSiteParams, true)) {
                    $exampleSiteParams[] = $param;
                }
            }
        }

        return $exampleSiteParams;
    }

    /**
     * @param  array<int, string>  $params
     * @return array<int, mixed>
     */
    private function getFormSchemaForParams(array $params): array
    {
        $schema = [];

        foreach ($params as $param) {
            if ($param === 'url') {
                $schema[] = TextInput::make('--url')
                    ->label(__('capell-admin::form.url'))
                    ->default(config('app.url'))
                    ->required();

                continue;
            }

            if ($param === 'languages') {
                $schema[] = LanguageSelect::make('--languages')
                    ->optionKey('code')
                    ->multiple()
                    ->withOptions();

                continue;
            }

            if ($param === 'sites') {
                $schema[] = SiteSelect::make('--sites')
                    ->optionKey('name')
                    ->multiple();
            }
        }

        return $schema;
    }
}
