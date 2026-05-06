<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Filament\Resources\BlockLibrary\Pages;

use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\BlockLibrary\Actions\MutateContentDataBeforeFillAction;
use Capell\BlockLibrary\Enums\ResourceEnum;
use Capell\BlockLibrary\Filament\Resources\BlockLibrary\ContentBlockResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContentBlock extends CreateRecord
{
    /** @return class-string<ContentBlockResource> */
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::ContentBlock);
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $this->form->fill(MutateContentDataBeforeFillAction::run($this->data));

        $this->callHook('afterFill');
    }
}
