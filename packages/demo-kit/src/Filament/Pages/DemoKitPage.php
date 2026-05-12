<?php

declare(strict_types=1);

namespace Capell\DemoKit\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Capell\Admin\Filament\Pages\ExtensionsPage;
use Capell\DemoKit\Actions\InsertExampleSiteDataAction;
use Capell\DemoKit\Support\Extensions\ExampleSiteDataActionSchema;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;
use Throwable;

final class DemoKitPage extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $slug = 'demo-kit';

    protected static ?int $navigationSort = 92;

    protected string $view = 'capell-demo-kit::filament.pages.demo-kit';

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) __('capell-demo-kit::page.navigation_label');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-admin::navigation.group_system');
    }

    #[Override]
    public static function canAccess(): bool
    {
        return ExtensionsPage::canManageExtensions();
    }

    #[Override]
    public function getTitle(): string
    {
        return (string) __('capell-demo-kit::page.title');
    }

    #[Override]
    public function getSubheading(): ?string
    {
        return (string) __('capell-demo-kit::page.subheading');
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('insertExampleSiteData')
                ->label(__('capell-demo-kit::actions.insert_example_site_data'))
                ->icon(Heroicon::OutlinedCircleStack)
                ->authorize(fn (): bool => ExtensionsPage::canManageExtensions())
                ->schema(fn (): array => resolve(ExampleSiteDataActionSchema::class)->schema())
                ->modalHeading(__('capell-demo-kit::actions.insert_example_site_data_heading'))
                ->modalDescription(__('capell-demo-kit::actions.insert_example_site_data_description'))
                ->successNotificationTitle(__('capell-demo-kit::actions.example_site_data_installed'))
                ->failureNotificationTitle(__('capell-demo-kit::actions.example_site_data_installation_failed'))
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
