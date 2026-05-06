<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Filament\Widgets\Health;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Diagnostics\Actions\Dashboard\BuildPackagesInstalledAction;
use Capell\Diagnostics\Data\Dashboard\PackagesInstalledData;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

final class PackagesInstalledWidgetAbstract extends Widget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['super_admin'];

    protected static string $settingsKey = 'packages_installed';

    protected string $view = 'capell-diagnostics::widgets.packages-installed';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 2];

    public static function getDescription(): string
    {
        return (string) __('capell-admin::dashboard.widget_packages_installed_description');
    }

    #[Computed(persist: true, seconds: 300)]
    public function data(): PackagesInstalledData
    {
        return BuildPackagesInstalledAction::run();
    }
}
