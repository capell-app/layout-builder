<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Filament\Widgets\Health;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\DeveloperTools\Actions\Dashboard\BuildMigrationsHealthAction;
use Capell\DeveloperTools\Data\Dashboard\MigrationsHealthData;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

final class MigrationsHealthWidgetAbstract extends Widget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['super_admin'];

    protected static string $settingsKey = 'migrations_health';

    protected string $view = 'capell-developer-tools::widgets.migrations-health';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    public static function getDescription(): string
    {
        return (string) __('capell-admin::dashboard.widget_migrations_health_description');
    }

    #[Computed(persist: true, seconds: 300)]
    public function data(): MigrationsHealthData
    {
        return BuildMigrationsHealthAction::run();
    }
}
