<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Filament\Widgets\Health;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\DeveloperTools\Actions\Dashboard\BuildSetupHealthAction;
use Capell\DeveloperTools\Data\Dashboard\SetupHealthData;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

final class SetupHealthWidgetAbstract extends Widget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = [];

    protected static string $settingsKey = 'setup_health';

    protected string $view = 'capell-developer-tools::widgets.setup-health';

    protected int|string|array $columnSpan = ['default' => 'full'];

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        if (! self::canViewCheck()) {
            return false;
        }

        return ! BuildSetupHealthAction::run()->allGreen;
    }

    #[Computed(persist: true, seconds: 300)]
    public function data(): SetupHealthData
    {
        return BuildSetupHealthAction::run();
    }
}
