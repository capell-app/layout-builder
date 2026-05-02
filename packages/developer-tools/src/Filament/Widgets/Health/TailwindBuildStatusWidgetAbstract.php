<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Filament\Widgets\Health;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\DeveloperTools\Actions\Dashboard\BuildTailwindBuildStatusAction;
use Capell\DeveloperTools\Data\Dashboard\TailwindBuildStatusData;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

final class TailwindBuildStatusWidgetAbstract extends Widget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['super_admin'];

    protected static string $settingsKey = 'tailwind_build_status';

    protected string $view = 'capell-developer-tools::widgets.tailwind-build-status';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    #[Computed(persist: true, seconds: 300)]
    public function data(): TailwindBuildStatusData
    {
        return BuildTailwindBuildStatusAction::run();
    }
}
