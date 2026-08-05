<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Widgets;

use Capell\Admin\Contracts\CapellFilamentWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\LayoutBuilder\Actions\BuildLayoutHealthWorkQueueAction;
use Capell\LayoutBuilder\Data\Dashboard\LayoutHealthData;
use Filament\Widgets\Widget as FilamentWidget;
use Override;

final class LayoutHealthFilamentWidget extends FilamentWidget implements CapellFilamentWidgetContract
{
    use GatedByRoleAndSettings;

    protected static string $settingsKey = 'layout_health';

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['super_admin'];

    protected string $view = 'capell-layout-builder::filament.widgets.layout-health';

    /**
     * @return array<string, mixed>
     */
    #[Override]
    protected function getViewData(): array
    {
        return [
            'data' => $this->getData(),
        ];
    }

    private function getData(): LayoutHealthData
    {
        return new LayoutHealthData(
            workQueue: BuildLayoutHealthWorkQueueAction::run(),
        );
    }
}
