<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Filament\Widgets\Health;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\DeveloperTools\Actions\Dashboard\BuildConfigDriftAction;
use Capell\DeveloperTools\Data\Dashboard\ConfigDriftData;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

final class ConfigDriftWidgetAbstract extends Widget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['super_admin'];

    protected static string $settingsKey = 'config_drift';

    protected string $view = 'capell-developer-tools::widgets.config-drift';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    public static function getDescription(): string
    {
        return (string) __('capell-developer-tools::package.widget_config_drift_description');
    }

    #[Computed(persist: true, seconds: 300)]
    public function data(): ConfigDriftData
    {
        return BuildConfigDriftAction::run();
    }
}
