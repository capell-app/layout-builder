<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Filament\Widgets\Health;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\DeveloperTools\Actions\Dashboard\BuildRegistryHealthAction;
use Capell\DeveloperTools\Data\Dashboard\RegistryHealthData;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

final class RegistryHealthWidgetAbstract extends Widget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['super_admin'];

    protected static string $settingsKey = 'registry_health';

    protected string $view = 'capell-developer-tools::widgets.registry-health';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    public static function getDescription(): string
    {
        return (string) __('capell-admin::dashboard.widget_registry_health_description');
    }

    #[Computed(persist: true, seconds: 300)]
    public function data(): RegistryHealthData
    {
        return BuildRegistryHealthAction::run();
    }
}
