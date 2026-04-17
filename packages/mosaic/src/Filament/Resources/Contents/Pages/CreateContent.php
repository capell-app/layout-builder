<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Contents\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Mosaic\Actions\MutateContentDataBeforeFillAction;
use Capell\Mosaic\Enums\ResourceEnum;
use Capell\Mosaic\Filament\Resources\Contents\ContentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContent extends CreateRecord
{
    /** @return class-string<ContentResource> */
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
