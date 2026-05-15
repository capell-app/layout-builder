<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Elements\Pages;

use Capell\Admin\Filament\Concerns\HasConfigurableFormActionPosition;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\LayoutBuilder\Enums\ResourceEnum;
use Capell\LayoutBuilder\Filament\Resources\Elements\ElementResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateElement extends CreateRecord
{
    use HasConfigurableFormActionPosition;

    /** @return class-string<ElementResource> */
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Element);
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
