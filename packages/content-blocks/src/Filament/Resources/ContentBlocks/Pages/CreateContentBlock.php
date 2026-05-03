<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Filament\Resources\ContentBlocks\Pages;

use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\ContentBlocks\Actions\MutateContentDataBeforeFillAction;
use Capell\ContentBlocks\Enums\ResourceEnum;
use Capell\ContentBlocks\Filament\Resources\ContentBlocks\ContentBlockResource;
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
