<?php

declare(strict_types=1);

namespace Capell\StarterSites\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Capell\Admin\Filament\Pages\ExtensionsPage;
use Capell\StarterSites\Actions\InsertExampleSiteDataAction;
use Capell\StarterSites\Support\Extensions\StarterSitesDemoActionSchema;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;
use Throwable;

final class StarterSitesPage extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $slug = 'starter-sites';

    protected static ?int $navigationSort = 92;

    protected string $view = 'capell-starter-sites::filament.pages.starter-sites';

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) __('capell-starter-sites::page.navigation_label');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-admin::navigation.group_administration');
    }

    #[Override]
    public static function canAccess(): bool
    {
        return ExtensionsPage::canManageExtensions();
    }

    #[Override]
    public function getTitle(): string
    {
        return (string) __('capell-starter-sites::page.title');
    }

    #[Override]
    public function getSubheading(): ?string
    {
        return (string) __('capell-starter-sites::page.subheading');
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('insertExampleSiteData')
                ->label(__('capell-starter-sites::actions.insert_example_site_data'))
                ->icon(Heroicon::OutlinedCircleStack)
                ->authorize(fn (): bool => ExtensionsPage::canManageExtensions())
                ->schema(fn (): array => resolve(StarterSitesDemoActionSchema::class)->schema())
                ->modalHeading(__('capell-starter-sites::actions.insert_example_site_data_heading'))
                ->modalDescription(__('capell-starter-sites::actions.insert_example_site_data_description'))
                ->successNotificationTitle(__('capell-starter-sites::actions.example_site_data_installed'))
                ->failureNotificationTitle(__('capell-starter-sites::actions.example_site_data_installation_failed'))
                ->action(function (Action $action, array $data): void {
                    try {
                        InsertExampleSiteDataAction::run($data);
                    } catch (Throwable $throwable) {
                        $action->failureNotificationBody($throwable->getMessage());
                        $action->failure();

                        return;
                    }

                    $action->success();
                }),
        ];
    }
}
