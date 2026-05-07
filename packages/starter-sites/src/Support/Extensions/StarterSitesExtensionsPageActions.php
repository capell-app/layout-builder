<?php

declare(strict_types=1);

namespace Capell\StarterSites\Support\Extensions;

use Capell\Admin\Filament\Pages\ExtensionsPage;
use Capell\Admin\Support\Extensions\ExtensionsPageActionRegistry;
use Capell\Core\Facades\CapellCore;
use Capell\StarterSites\Providers\StarterSitesServiceProvider;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Throwable;

final class StarterSitesExtensionsPageActions
{
    public function register(ExtensionsPageActionRegistry $registry): void
    {
        $registry->registerHeaderAction(fn (ExtensionsPage $page): Action => $this->installExampleSiteDataAction());
    }

    private function installExampleSiteDataAction(): Action
    {
        return Action::make('installExampleSiteData')
            ->label(__('capell-starter-sites::actions.install_example_site_data'))
            ->icon(Heroicon::OutlinedBugAnt)
            ->outlined()
            ->size('sm')
            ->color('gray')
            ->authorize(fn (): bool => ExtensionsPage::canManageExtensions())
            ->visible(fn (): bool => $this->canInstallExampleSiteData())
            ->schema(fn (): array => $this->getExampleSiteActionSchema())
            ->modalHeading(__('capell-starter-sites::actions.install_example_site_data_heading'))
            ->modalDescription(__('capell-starter-sites::actions.install_example_site_data_description'))
            ->successNotificationTitle(__('capell-starter-sites::actions.example_site_data_installed'))
            ->failureNotificationTitle(__('capell-starter-sites::actions.example_site_data_installation_failed'))
            ->action(function (ExtensionsPage $livewire, Action $action, array $data): void {
                $package = CapellCore::getPackage(StarterSitesServiceProvider::$packageName);
                $demoCommand = $package->getDemoCommand();

                if ($demoCommand === null) {
                    $action->failureNotificationBody(__('capell-starter-sites::actions.example_site_data_command_missing'));
                    $action->failure();

                    return;
                }

                try {
                    Artisan::call($demoCommand, $this->commandParams($data));
                } catch (Throwable $throwable) {
                    $action->failureNotificationBody($throwable->getMessage());
                    $action->failure();

                    return;
                }

                $action->success();
                $livewire->dispatch('refresh-table');
            });
    }

    private function canInstallExampleSiteData(): bool
    {
        $enabled = config('capell-starter-sites.extensions_page_action_enabled');

        return ExtensionsPage::canManageExtensions()
            && (is_bool($enabled) ? $enabled : app()->isLocal() && ! app()->isProduction());
    }

    /** @return array<int, mixed> */
    private function getExampleSiteActionSchema(): array
    {
        return resolve(StarterSitesActionSchemaRegistry::class)->get('demo');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function commandParams(array $data): array
    {
        $params = [
            '--force' => true,
        ];

        foreach (['url', 'user', 'languages', 'sites'] as $param) {
            if (! array_key_exists($param, $data)) {
                continue;
            }

            if ($data[$param] === null || $data[$param] === '' || $data[$param] === []) {
                continue;
            }

            $params['--' . $param] = $data[$param];
        }

        return $params;
    }
}
