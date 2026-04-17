<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Collections\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Layout\Actions\MutateContentDataBeforeFillAction;
use Capell\Layout\Enums\ResourceEnum;
use Capell\Layout\Filament\Resources\Collections\CollectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCollection extends CreateRecord
{
    /** @return class-string<CollectionResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Content);
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $this->form->fill(MutateContentDataBeforeFillAction::run($this->data));

        $this->callHook('afterFill');
    }
}
