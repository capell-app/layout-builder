<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Widgets\Pages;

use Capell\Admin\Filament\Concerns\HasConfigurableFormActionPosition;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Core\LayoutBuilder\Enums\ResourceEnum;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateWidget extends CreateRecord
{
    use HasConfigurableFormActionPosition;

    /** @return class-string<WidgetResource> */
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Widget);
    }

    /**
     * @return array<Action>
     */
    protected function getPositionedFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getPositionedHeaderFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->submit(null)
                ->action(fn (): mixed => $this->create()),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }
}
