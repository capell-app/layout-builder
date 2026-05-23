<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Blocks\Pages;

use Capell\Admin\Filament\Concerns\HasConfigurableFormActionPosition;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\LayoutBuilder\Enums\ResourceEnum;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Override;

class CreateBlock extends CreateRecord
{
    use HasConfigurableFormActionPosition;

    /** @return class-string<resource> */
    #[Override]
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Block);
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
                ->action(function (): void {
                    $this->create();
                }),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }
}
